<?php

use App\Actions\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\OrderNotPlacedException;
use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\Settings;
use App\Support\CheckoutDetails;
use App\Support\QatarPhone;
use Illuminate\Support\Facades\Mail;

/**
 * Build checkout details, delivering to a new area with a QAR 25 fee unless one is given.
 *
 * @param  array<string, mixed>  $overrides  named CheckoutDetails arguments
 */
function checkoutDetails(array $overrides = []): CheckoutDetails
{
    return new CheckoutDetails(...[
        'name' => 'Fatima Al-Kuwari',
        'phone' => QatarPhone::tryParse('5555 1234'),
        'email' => null,
        'deliveryZone' => $overrides['deliveryZone'] ?? DeliveryZone::factory()->create(['fee' => 2500]),
        'zoneNumber' => '45',
        'streetNumber' => '330',
        'buildingNumber' => '12',
        'unit' => null,
        'landmark' => null,
        'latitude' => null,
        'longitude' => null,
        'preferredDate' => null,
        'note' => null,
        'paymentMethod' => PaymentMethod::CashOnDelivery,
        'locale' => 'en',
        ...$overrides,
    ]);
}

/**
 * Place an order through the action.
 *
 * @param  array<int, int>  $quantities  pieces keyed by product variant ID
 */
function placeOrder(array $quantities, ?CheckoutDetails $details = null, ?int $expectedSubtotal = null): Order
{
    return app(PlaceOrder::class)->handle($quantities, $details ?? checkoutDetails(), $expectedSubtotal);
}

/**
 * Find a product's variant by color and size slug.
 */
function variantOf(Product $product, string $colorSlug, string $sizeSlug): ProductVariant
{
    return $product->variants()
        ->whereRelation('productColor.color', 'slug', $colorSlug)
        ->whereRelation('size', 'slug', $sizeSlug)
        ->sole();
}

beforeEach(fn () => Mail::fake());

it('places a cash on delivery order and takes stock from the exact color and size', function () {
    $this->travelTo('2026-11-09 10:00:00');
    $product = listedProduct(['price' => 18500], ['navy' => ['54' => 4, '56' => 3], 'olive' => ['56' => 3]]);
    $navy56 = variantOf($product, 'navy', '56');

    $order = placeOrder([$navy56->id => 2]);

    expect($order->number)->toBe(Order::numberFor($order->id))->toStartWith('DW-');
    expect($order->status)->toBe(OrderStatus::PendingConfirmation);
    expect([$order->subtotal, $order->delivery_fee, $order->grand_total])->toBe([37000, 2500, 39500]);
    expect($order->delivery_estimate_from->toDateString())->toBe('2026-11-10');
    expect($order->delivery_estimate_to->toDateString())->toBe('2026-11-12');

    expect($navy56->refresh()->stock)->toBe(1);
    expect(variantOf($product, 'navy', '54')->stock)->toBe(4);
    expect(variantOf($product, 'olive', '56')->stock)->toBe(3);

    $movement = StockMovement::query()->sole();
    expect($movement->reason)->toBe(StockMovementReason::OrderPlaced);
    expect([$movement->change, $movement->stock_after, $movement->order_id, $movement->note])->toBe([-2, 1, $order->id, $order->number]);

    $payment = $order->payments()->sole();
    expect([$payment->method, $payment->status, $payment->amount])->toBe([PaymentMethod::CashOnDelivery, PaymentStatus::Pending, 39500]);

    $customer = Customer::query()->sole();
    expect([$customer->phone, $customer->name])->toBe(['+97455551234', 'Fatima Al-Kuwari']);
    expect($order->customer_id)->toBe($customer->id);
});

it('keeps a copy of each line that survives product changes', function () {
    $product = listedProduct(['name_en' => 'Classic crepe jelabiya', 'price' => 18500, 'compare_at_price' => 22000]);
    $variant = $product->variants()->sole();

    $order = placeOrder([$variant->id => 1]);
    $product->update(['name_en' => 'Renamed jelabiya', 'price' => 25000]);
    $variant->delete();

    $item = $order->items()->sole();
    expect($item->product_name_en)->toBe('Classic crepe jelabiya');
    expect([$item->color_name_en, $item->size_name_en, $item->sku])->toBe(['Navy', '56', $variant->sku]);
    expect([$item->unit_price, $item->compare_at_price, $item->quantity, $item->line_total])->toBe([18500, 22000, 1, 18500]);
    expect($item->product_variant_id)->toBeNull();
});

it('lets only one shopper buy the last piece', function () {
    $variant = listedProduct(['name_en' => 'Embroidered kaftan'], ['navy' => ['56' => 1]])->variants()->sole();
    placeOrder([$variant->id => 1]);

    expect(fn () => placeOrder([$variant->id => 1], checkoutDetails(['phone' => QatarPhone::tryParse('6666 1234')])))
        ->toThrow(OrderNotPlacedException::class, 'Sorry, Embroidered kaftan (Navy, size 56) just sold out.');

    expect($variant->refresh()->stock)->toBe(0);
    $this->assertDatabaseCount('orders', 1);
    expect(StockMovement::query()->where('reason', StockMovementReason::OrderPlaced)->count())->toBe(1);
});

it('takes nothing when one line has fewer pieces left than ordered', function () {
    $plenty = listedProduct(['name_en' => 'Classic crepe jelabiya'], ['navy' => ['56' => 5]])->variants()->sole();
    $short = listedProduct(['name_en' => 'Embroidered kaftan'], ['olive' => ['58' => 1]])->variants()->sole();

    expect(fn () => placeOrder([$plenty->id => 2, $short->id => 3]))
        ->toThrow(OrderNotPlacedException::class, 'Sorry, only 1 left of Embroidered kaftan (Olive, size 58).');

    expect($plenty->refresh()->stock)->toBe(5);
    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('customers', 0);
    $this->assertDatabaseCount('stock_movements', 0);
});

it('refuses products that are no longer sold', function () {
    $variant = listedProduct(['status' => 'draft'])->variants()->sole();

    expect(fn () => placeOrder([$variant->id => 1]))->toThrow(OrderNotPlacedException::class, 'just sold out');

    $this->assertDatabaseCount('orders', 0);
});

it('stops when the subtotal differs from what the shopper saw', function () {
    $variant = listedProduct(['price' => 18500])->variants()->sole();

    expect(fn () => placeOrder([$variant->id => 1], expectedSubtotal: 15000))
        ->toThrow(OrderNotPlacedException::class, 'Your order changed since you opened this page.');

    expect($variant->refresh()->stock)->toBe(3);
    $this->assertDatabaseCount('orders', 0);
});

it('refuses phone numbers the team blocked', function () {
    Customer::factory()->blocked()->create(['phone' => '+97455551234']);
    $variant = listedProduct()->variants()->sole();

    expect(fn () => placeOrder([$variant->id => 1]))->toThrow(OrderNotPlacedException::class, 'Please contact us on WhatsApp to order.');

    $this->assertDatabaseCount('orders', 0);
});

it('recognises returning customers by phone number', function () {
    $customer = Customer::factory()->create(['phone' => '+97455551234', 'name' => 'F. Kuwari', 'email' => 'fatima@example.com']);
    $variant = listedProduct()->variants()->sole();

    $order = placeOrder([$variant->id => 1], checkoutDetails(['name' => 'Fatima Al-Kuwari', 'locale' => 'ar']));

    expect($order->customer_id)->toBe($customer->id);
    $customer->refresh();
    expect([$customer->name, $customer->email, $customer->locale])->toBe(['Fatima Al-Kuwari', 'fatima@example.com', 'ar']);
    $this->assertDatabaseCount('customers', 1);
});

it('delivers free from the free delivery amount', function () {
    app(Settings::class)->set(['delivery_free_over' => 30000]);
    $variant = listedProduct(['price' => 18500])->variants()->sole();

    $order = placeOrder([$variant->id => 2]);

    expect([$order->delivery_fee, $order->grand_total])->toBe([0, 37000]);
});

it('emails the customer in their language and the store team', function () {
    app(Settings::class)->set(['order_alert_email' => 'orders@example.com']);
    $variant = listedProduct()->variants()->sole();

    $order = placeOrder([$variant->id => 1], checkoutDetails(['email' => 'fatima@example.com', 'locale' => 'ar']));

    Mail::assertQueued(OrderPlaced::class, fn (OrderPlaced $mail): bool => $mail->hasTo('fatima@example.com') && $mail->locale === 'ar' && $mail->order->is($order));
    Mail::assertQueued(NewOrderAlert::class, fn (NewOrderAlert $mail): bool => $mail->hasTo('orders@example.com') && $mail->order->is($order));
});

it('sends no emails without an address to send them to', function () {
    $variant = listedProduct()->variants()->sole();

    placeOrder([$variant->id => 1]);

    Mail::assertNothingQueued();
});
