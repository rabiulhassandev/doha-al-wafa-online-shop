<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\PlaceOrder;
use App\Actions\SaveAddress;
use App\Exceptions\OrderNotPlacedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Models\Address;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\Payments\PaymentGateways;
use App\Services\Settings;
use App\Support\CartLine;
use App\Support\CartSummary;
use App\Support\Money;
use App\Support\QatarPhone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly Settings $settings,
    ) {}

    /**
     * Show the checkout form for the "Order now" item if there is one, otherwise for the bag.
     *
     * Opening checkout from the bag ("?from=bag") drops any earlier "Order now" item.
     */
    public function show(Request $request, PaymentGateways $paymentGateways): View|RedirectResponse
    {
        if ($request->query('from') === 'bag') {
            $request->session()->forget(BuyNowController::SESSION_KEY);
        }

        [$summary, $buyNowVariant] = $this->orderSummary($request, $this->cartService->deliveryZone($request));

        if ($summary->isEmpty() || $summary->hasUnavailableLines()) {
            return $this->leaveCheckout($request, $summary, $buyNowVariant);
        }

        $deliveryZones = DeliveryZone::query()->active()->ordered()->get();

        return view('storefront.checkout.show', [
            'summary' => $summary,
            'isBuyNow' => $buyNowVariant !== null,
            'deliveryZones' => $deliveryZones,
            'zoneOptions' => $this->zoneOptions($deliveryZones, $summary),
            'paymentMethods' => $paymentGateways->enabledMethods(),
            'preferredDateEnabled' => (bool) $this->settings->get('preferred_date_enabled'),
            'checkoutToken' => (string) Str::uuid(),
            'account' => $this->accountPrefill($request->user()),
        ]);
    }

    /**
     * Place the order and show the thank-you page, or the gateway's payment page for online payments.
     */
    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder, PaymentGateways $paymentGateways, SaveAddress $saveAddress): RedirectResponse
    {
        $checkoutToken = $request->validated('checkout_token');
        $alreadyPlaced = Order::query()->firstWhere('checkout_token', $checkoutToken);

        if ($alreadyPlaced !== null) {
            return redirect($alreadyPlaced->confirmationUrl());
        }

        [$summary, $buyNowVariant] = $this->orderSummary($request, null);

        if ($summary->isEmpty() || $summary->hasUnavailableLines()) {
            return $this->leaveCheckout($request, $summary, $buyNowVariant);
        }

        $quantities = $summary->lines
            ->mapWithKeys(fn (CartLine $line): array => [$line->variant->id => $line->quantity])
            ->all();

        try {
            $order = $placeOrder->handle($quantities, $request->details(), (int) $request->validated('expected_subtotal'));
        } catch (OrderNotPlacedException $exception) {
            return to_route('checkout.show')->withInput()->withErrors(['order' => $exception->getMessage()]);
        } catch (UniqueConstraintViolationException $exception) {
            // A second tap on "Place order" lost the race to the first one.
            $alreadyPlaced = Order::query()->firstWhere('checkout_token', $checkoutToken) ?? throw $exception;

            return redirect($alreadyPlaced->confirmationUrl());
        }

        $this->forgetOrderedItems($request, $buyNowVariant, array_keys($quantities));
        $request->session()->put(CartService::DELIVERY_ZONE_SESSION_KEY, $order->delivery_zone_id);
        $request->session()->forget(CartService::COUPON_SESSION_KEY);

        if ($request->user() !== null && $request->boolean('save_address')) {
            $this->rememberAddress($request->user(), $order, $saveAddress);
        }

        $payment = $order->payments()->latest('id')->firstOrFail();

        return redirect($paymentGateways->for($order->payment_method)->paymentUrl($payment) ?? $order->confirmationUrl());
    }

    /**
     * Price the "Order now" item, or the bag when there is none.
     *
     * @return array{CartSummary, ProductVariant|null}
     */
    private function orderSummary(Request $request, ?DeliveryZone $deliveryZone): array
    {
        $buyNow = $request->session()->get(BuyNowController::SESSION_KEY);
        $buyNowVariant = is_array($buyNow) ? ProductVariant::query()->find($buyNow['variant_id'] ?? null) : null;

        $coupon = $this->cartService->coupon($request);
        $summary = $buyNowVariant !== null
            ? $this->cartService->summaryFor($buyNowVariant, (int) $buyNow['quantity'], $deliveryZone, $coupon)
            : $this->cartService->summary($this->cartService->current($request), $deliveryZone, $coupon);

        return [$summary, $buyNowVariant];
    }

    /**
     * Send shoppers back when there is nothing to order or something sold out: to the bag, which
     * marks sold out lines, or to the product for a sold out "Order now" item.
     */
    private function leaveCheckout(Request $request, CartSummary $summary, ?ProductVariant $buyNowVariant): RedirectResponse
    {
        if ($buyNowVariant === null || $summary->isEmpty()) {
            return to_route('cart.show');
        }

        $request->session()->forget(BuyNowController::SESSION_KEY);

        return to_route('products.show', ['product' => $buyNowVariant->product])
            ->withErrors(['variant_id' => __('Sorry, this size just sold out.')]);
    }

    /**
     * Take the ordered pieces out of the bag, or drop the "Order now" item.
     *
     * @param  list<int>  $variantIds
     */
    private function forgetOrderedItems(Request $request, ?ProductVariant $buyNowVariant, array $variantIds): void
    {
        if ($buyNowVariant !== null) {
            $request->session()->forget(BuyNowController::SESSION_KEY);

            return;
        }

        $this->cartService->current($request)?->items()->whereIn('product_variant_id', $variantIds)->delete();
    }

    /**
     * Get what checkout fills in for a signed-in shopper: their details and saved addresses, the first one used first.
     *
     * @return array{name: string, email: string, phone: string|null, addresses: array<int, array{label: string, fields: array<string, string>}>, address: array<string, string>|null}|null
     */
    private function accountPrefill(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $addresses = $user->addresses()->with('deliveryZone')->get();

        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => QatarPhone::tryParse($user->phone)?->formatted(),
            'addresses' => $addresses->mapWithKeys(fn (Address $address): array => [$address->id => [
                'label' => collect([$address->label, $address->deliveryZone?->translated('name'), $address->plateAddress()])->filter()->implode(' · '),
                'fields' => $address->checkoutFields(),
            ]])->all(),
            'address' => $addresses->first()?->checkoutFields(),
        ];
    }

    /**
     * Save the order's address on the account, unless the same address is already saved.
     */
    private function rememberAddress(User $user, Order $order, SaveAddress $saveAddress): void
    {
        $attributes = [
            'delivery_zone_id' => $order->delivery_zone_id,
            'zone_number' => $order->zone_number,
            'street_number' => $order->street_number,
            'building_number' => $order->building_number,
        ];

        if ($user->addresses()->where($attributes)->exists()) {
            return;
        }

        $saveAddress->handle($user, [...$attributes, 'unit' => $order->unit, 'landmark' => $order->landmark]);
    }

    /**
     * Get each delivery area's fee, discount, total and choosable delivery days, so the form updates without reloading.
     *
     * @param  Collection<int, DeliveryZone>  $deliveryZones
     * @return array<int, array{id: int, feeText: string, discountText: string|null, totalText: string, dates: list<array{value: string, label: string}>}>
     */
    private function zoneOptions(Collection $deliveryZones, CartSummary $summary): array
    {
        $locale = app()->getLocale();
        $now = CarbonImmutable::now();
        $daysOff = $this->settings->deliveryDaysOff();

        return $deliveryZones->mapWithKeys(function (DeliveryZone $zone) use ($locale, $now, $daysOff, $summary): array {
            $zoneSummary = $summary->forDeliveryZone($zone);
            $fee = $zoneSummary->deliveryFee();
            $discount = $zoneSummary->discount();

            return [$zone->id => [
                'id' => $zone->id,
                'feeText' => $fee === 0 ? __('Free') : Money::format($fee, $locale),
                'discountText' => $discount > 0 ? '−'.Money::format($discount, $locale) : null,
                'totalText' => Money::format($zoneSummary->total(), $locale),
                'dates' => array_map(
                    fn (CarbonImmutable $day): array => ['value' => $day->toDateString(), 'label' => $day->locale($locale)->translatedFormat('l j F')],
                    $zone->choosableDeliveryDays($now, $daysOff),
                ),
            ]];
        })->all();
    }
}
