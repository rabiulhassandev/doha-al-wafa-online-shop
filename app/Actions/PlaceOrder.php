<?php

namespace App\Actions;

use App\Enums\CartLineStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\OrderNotPlacedException;
use App\Jobs\SendPurchaseToAdPlatforms;
use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\ConversionEvents;
use App\Services\Payments\PaymentGateways;
use App\Services\Settings;
use App\Support\CartLine;
use App\Support\CartSummary;
use App\Support\CheckoutDetails;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Turns a checkout into an order: takes the stock, keeps a copy of every line as sold,
 * records the payment and emails the customer and the store.
 */
class PlaceOrder
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly AdjustStock $adjustStock,
        private readonly PaymentGateways $paymentGateways,
        private readonly Settings $settings,
        private readonly ConversionEvents $conversionEvents,
    ) {}

    /**
     * Place an order for the given pieces.
     *
     * Stock, availability and prices are checked again while the stock rows are locked, so two
     * shoppers can never buy the same last piece. Nothing is saved unless the whole order goes through.
     *
     * Orders the team enters by hand ($placedBy) start confirmed, since the team already spoke to
     * the customer, and are not held back by the blocked phone list.
     *
     * @param  array<int, int>  $quantities  pieces wanted, keyed by product variant ID
     * @param  int|null  $expectedSubtotal  the subtotal the shopper saw, in dirhams; any other subtotal stops the order
     * @param  User|null  $placedBy  the team member entering the order by hand
     *
     * @throws OrderNotPlacedException
     */
    public function handle(array $quantities, CheckoutDetails $details, ?int $expectedSubtotal = null, ?User $placedBy = null): Order
    {
        $quantities = array_filter($quantities, fn (int $quantity): bool => $quantity > 0);

        if ($quantities === []) {
            throw OrderNotPlacedException::nothingToOrder();
        }

        if ($placedBy === null && Customer::isBlocked($details->phone)) {
            throw OrderNotPlacedException::blockedPhone();
        }

        $order = DB::transaction(function () use ($quantities, $details, $expectedSubtotal, $placedBy): Order {
            $coupon = $this->lockCoupon($details);
            $summary = $this->lockAndPrice($quantities, $details->deliveryZone, $coupon, withinShopperLimit: $placedBy === null);

            if ($expectedSubtotal !== null && $summary->subtotal() !== $expectedSubtotal) {
                throw OrderNotPlacedException::orderChanged();
            }

            $order = $this->createOrder($this->saveCustomer($details), $summary, $details, $placedBy);

            foreach ($summary->lines as $line) {
                $order->items()->create(OrderItem::attributesFor($line));
                $this->adjustStock->change($line->variant, -$line->quantity, StockMovementReason::OrderPlaced, $placedBy, $order->number, order: $order);
            }

            if ($order->coupon_id !== null) {
                $coupon->redemptions()->create([
                    'order_id' => $order->id,
                    'phone' => $order->customer_phone,
                    'discount' => $order->discount_total,
                ]);
            }

            $this->paymentGateways->for($details->paymentMethod)->createPayment($order);

            if ($placedBy !== null) {
                $order->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => $order->status,
                    'note' => "Entered by hand from {$details->channel->label()}.",
                    'user_id' => $placedBy->id,
                ]);
            }

            return $order;
        });

        $this->sendEmails($order, alertTheTeam: $placedBy === null);

        if ($order->ad_tracking_allowed && $this->conversionEvents->isConfigured()) {
            SendPurchaseToAdPlatforms::dispatch($order)->afterCommit();
        }

        return $order;
    }

    /**
     * Lock the discount code, so two orders cannot both take its last use, and check this customer may use it.
     *
     * A code that only falls short of its minimum spend is not an error: the order is placed without the
     * discount, as the shopper's totals already showed.
     */
    private function lockCoupon(CheckoutDetails $details): ?Coupon
    {
        if (blank($details->couponCode)) {
            return null;
        }

        $code = Coupon::normalizeCode($details->couponCode);
        $coupon = Coupon::query()->lockForUpdate()->firstWhere('code', $code);
        $problem = $coupon === null
            ? __('The code :code is not valid.', ['code' => $code])
            : $coupon->availabilityProblem() ?? $coupon->problemForPhone($details->phone);

        if ($problem !== null) {
            throw OrderNotPlacedException::couponProblem($problem);
        }

        return $coupon;
    }

    /**
     * Lock the ordered colors and sizes and price them, stopping when one cannot be ordered as asked.
     *
     * @param  array<int, int>  $quantities
     * @param  bool  $withinShopperLimit  false for orders the team enters, which may pass the per-item limit
     */
    private function lockAndPrice(array $quantities, DeliveryZone $deliveryZone, ?Coupon $coupon, bool $withinShopperLimit): CartSummary
    {
        // Locking in ID order keeps two orders for the same pieces from waiting on each other forever.
        $variants = ProductVariant::query()
            ->whereKey(array_keys($quantities))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->load(['product', 'size', 'productColor.color', 'productColor.images'])
            ->keyBy('id');

        $lines = collect($quantities)->map(function (int $quantity, int $variantId) use ($variants, $withinShopperLimit): CartLine {
            $variant = $variants->get($variantId) ?? throw OrderNotPlacedException::orderChanged();
            $line = $this->cartService->line($variant, $quantity, withinShopperLimit: $withinShopperLimit);

            if ($line->status !== CartLineStatus::Available) {
                throw OrderNotPlacedException::notEnoughStock($line);
            }

            return $line;
        });

        return new CartSummary($lines->values(), $deliveryZone, $this->cartService->storeFreeDeliveryThreshold(), $coupon);
    }

    /**
     * Find the customer by phone number, or add them, keeping their latest name, email and language.
     */
    private function saveCustomer(CheckoutDetails $details): Customer
    {
        $customer = Customer::query()->createOrFirst(
            ['phone' => $details->phone->e164()],
            ['name' => $details->name, 'email' => $details->email, 'locale' => $details->locale],
        );

        if (! $customer->wasRecentlyCreated) {
            $customer->update([
                'name' => $details->name,
                'email' => $details->email ?? $customer->email,
                'locale' => $details->locale,
            ]);
        }

        return $customer;
    }

    private function createOrder(Customer $customer, CartSummary $summary, CheckoutDetails $details, ?User $placedBy): Order
    {
        $placedAt = CarbonImmutable::now();
        $zone = $details->deliveryZone;
        [$estimateFrom, $estimateTo] = $zone->deliveryWindow($placedAt, $this->settings->deliveryDaysOff());
        $deliveryFee = $details->deliveryFee ?? $summary->deliveryFee();
        // A code that falls short of its minimum spend gives no discount and is not recorded on the order.
        $appliedCoupon = $summary->couponProblem === null ? $summary->coupon : null;

        $order = $customer->orders()->create([
            'user_id' => $details->userId,
            'checkout_token' => $details->checkoutToken,
            'channel' => $details->channel,
            'status' => $placedBy === null ? OrderStatus::PendingConfirmation : OrderStatus::Confirmed,
            'payment_method' => $details->paymentMethod,
            'payment_status' => PaymentStatus::Pending,
            'currency' => 'QAR',
            'subtotal' => $summary->subtotal(),
            'discount_total' => $summary->discount(),
            'coupon_id' => $appliedCoupon?->id,
            'coupon_code' => $appliedCoupon?->code,
            'delivery_fee' => $deliveryFee,
            'tax_total' => 0,
            'grand_total' => $summary->subtotal() - $summary->discount() + $deliveryFee,
            'customer_name' => $details->name,
            'customer_phone' => $details->phone->e164(),
            'customer_email' => $details->email,
            'delivery_zone_id' => $zone->id,
            'delivery_zone_name_en' => $zone->name_en,
            'delivery_zone_name_ar' => $zone->name_ar,
            'zone_number' => $details->zoneNumber,
            'street_number' => $details->streetNumber,
            'building_number' => $details->buildingNumber,
            'unit' => $details->unit,
            'landmark' => $details->landmark,
            'latitude' => $details->latitude,
            'longitude' => $details->longitude,
            'preferred_delivery_date' => $details->preferredDate,
            'delivery_estimate_from' => $estimateFrom,
            'delivery_estimate_to' => $estimateTo,
            'customer_note' => $details->note,
            'locale' => $details->locale,
            'ip_address' => $details->ipAddress,
            'user_agent' => $details->userAgent,
            'placed_at' => $placedAt,
            ...($details->attribution?->orderAttributes() ?? []),
            'ad_tracking_allowed' => $details->adTrackingAllowed,
            'tracking_ids' => $details->trackingIds === [] ? null : $details->trackingIds,
        ]);

        $order->number = Order::numberFor($order->id);
        $order->save();

        return $order;
    }

    private function sendEmails(Order $order, bool $alertTheTeam): void
    {
        if ($order->customer_email !== null) {
            Mail::to($order->customer_email, $order->customer_name)->locale($order->locale)->send(new OrderPlaced($order));
        }

        $alertEmail = $this->settings->get('order_alert_email');

        if ($alertTheTeam && filled($alertEmail)) {
            Mail::to($alertEmail)->locale('en')->send(new NewOrderAlert($order));
        }
    }
}
