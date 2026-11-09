<?php

namespace App\Actions;

use App\Enums\CartLineStatus;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\OrderNotPlacedException;
use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\CartService;
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
    ) {}

    /**
     * Place an order for the given pieces.
     *
     * Stock, availability and prices are checked again while the stock rows are locked, so two
     * shoppers can never buy the same last piece. Nothing is saved unless the whole order goes through.
     *
     * @param  array<int, int>  $quantities  pieces wanted, keyed by product variant ID
     * @param  int|null  $expectedSubtotal  the subtotal the shopper saw, in dirhams; any other subtotal stops the order
     *
     * @throws OrderNotPlacedException
     */
    public function handle(array $quantities, CheckoutDetails $details, ?int $expectedSubtotal = null): Order
    {
        $quantities = array_filter($quantities, fn (int $quantity): bool => $quantity > 0);

        if ($quantities === []) {
            throw OrderNotPlacedException::nothingToOrder();
        }

        if (Customer::isBlocked($details->phone)) {
            throw OrderNotPlacedException::blockedPhone();
        }

        $order = DB::transaction(function () use ($quantities, $details, $expectedSubtotal): Order {
            $summary = $this->lockAndPrice($quantities, $details->deliveryZone);

            if ($expectedSubtotal !== null && $summary->subtotal() !== $expectedSubtotal) {
                throw OrderNotPlacedException::orderChanged();
            }

            $order = $this->createOrder($this->saveCustomer($details), $summary, $details);

            foreach ($summary->lines as $line) {
                $order->items()->create($this->itemSnapshot($line));
                $this->adjustStock->change($line->variant, -$line->quantity, StockMovementReason::OrderPlaced, note: $order->number, order: $order);
            }

            $this->paymentGateways->for($details->paymentMethod)->createPayment($order);

            return $order;
        });

        $this->sendEmails($order);

        return $order;
    }

    /**
     * Lock the ordered colors and sizes and price them, stopping when one cannot be ordered as asked.
     *
     * @param  array<int, int>  $quantities
     */
    private function lockAndPrice(array $quantities, DeliveryZone $deliveryZone): CartSummary
    {
        // Locking in ID order keeps two orders for the same pieces from waiting on each other forever.
        $variants = ProductVariant::query()
            ->whereKey(array_keys($quantities))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->load(['product', 'size', 'productColor.color', 'productColor.images'])
            ->keyBy('id');

        $lines = collect($quantities)->map(function (int $quantity, int $variantId) use ($variants): CartLine {
            $variant = $variants->get($variantId) ?? throw OrderNotPlacedException::orderChanged();
            $line = $this->cartService->line($variant, $quantity);

            if ($line->status !== CartLineStatus::Available) {
                throw OrderNotPlacedException::notEnoughStock($line);
            }

            return $line;
        });

        return new CartSummary($lines->values(), $deliveryZone, $this->cartService->storeFreeDeliveryThreshold());
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

    private function createOrder(Customer $customer, CartSummary $summary, CheckoutDetails $details): Order
    {
        $placedAt = CarbonImmutable::now();
        $zone = $details->deliveryZone;
        [$estimateFrom, $estimateTo] = $zone->deliveryWindow($placedAt, $this->settings->deliveryDaysOff());

        $order = $customer->orders()->create([
            'checkout_token' => $details->checkoutToken,
            'channel' => OrderChannel::Web,
            'status' => OrderStatus::PendingConfirmation,
            'payment_method' => $details->paymentMethod,
            'payment_status' => PaymentStatus::Pending,
            'currency' => 'QAR',
            'subtotal' => $summary->subtotal(),
            'discount_total' => 0,
            'delivery_fee' => $summary->deliveryFee(),
            'tax_total' => 0,
            'grand_total' => $summary->total(),
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
        ]);

        $order->number = Order::numberFor($order->id);
        $order->save();

        return $order;
    }

    /**
     * Copy what was sold, so the order reads the same after the product changes or is deleted.
     *
     * @return array<string, mixed>
     */
    private function itemSnapshot(CartLine $line): array
    {
        $variant = $line->variant;
        $productColor = $variant->productColor;

        return [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name_en' => $variant->product->name_en,
            'product_name_ar' => $variant->product->name_ar,
            'color_name_en' => $productColor->displayName('en'),
            'color_name_ar' => $productColor->displayName('ar'),
            'color_hex' => $productColor->color->hex,
            'size_name_en' => $variant->size->name_en,
            'size_name_ar' => $variant->size->name_ar,
            'sku' => $variant->sku,
            'image_path' => $productColor->images->first()?->path(400),
            'unit_price' => $line->unitPrice,
            'compare_at_price' => $line->compareAtPrice,
            'quantity' => $line->quantity,
            'line_total' => $line->total(),
        ];
    }

    private function sendEmails(Order $order): void
    {
        if ($order->customer_email !== null) {
            Mail::to($order->customer_email, $order->customer_name)->locale($order->locale)->send(new OrderPlaced($order));
        }

        $alertEmail = $this->settings->get('order_alert_email');

        if (filled($alertEmail)) {
            Mail::to($alertEmail)->locale('en')->send(new NewOrderAlert($order));
        }
    }
}
