<?php

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\OrderNotPlacedException;
use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Settings;
use App\Support\QatarPhone;
use Illuminate\Support\Facades\Mail;

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

describe('discount codes', function () {
    it('takes the discount off the order and records the use', function () {
        $coupon = Coupon::factory()->create(['code' => 'EID10', 'value' => 10]);
        $variant = listedProduct(['price' => 18500])->variants()->sole();

        $order = placeOrder([$variant->id => 2], checkoutDetails(['couponCode' => 'eid10']));

        expect([$order->subtotal, $order->discount_total, $order->delivery_fee, $order->grand_total])->toBe([37000, 3700, 2500, 35800]);
        expect([$order->coupon_id, $order->coupon_code])->toBe([$coupon->id, 'EID10']);
        $redemption = $coupon->redemptions()->sole();
        expect([$redemption->order_id, $redemption->phone, $redemption->discount])->toBe([$order->id, '+97455551234', 3700]);
        expect($order->payments()->sole()->amount)->toBe(35800);
    });

    it('takes the delivery fee off with a free delivery code', function () {
        Coupon::factory()->freeDelivery()->create(['code' => 'FREESHIP']);
        $variant = listedProduct(['price' => 18500])->variants()->sole();

        $order = placeOrder([$variant->id => 1], checkoutDetails(['couponCode' => 'FREESHIP']));

        expect([$order->delivery_fee, $order->discount_total, $order->grand_total])->toBe([2500, 2500, 18500]);
    });

    it('places the order without the discount when the bag is under the minimum spend', function () {
        $coupon = Coupon::factory()->create(['code' => 'BIG50', 'min_subtotal' => 50000]);
        $variant = listedProduct(['price' => 18500])->variants()->sole();

        $order = placeOrder([$variant->id => 1], checkoutDetails(['couponCode' => 'BIG50']));

        expect([$order->discount_total, $order->coupon_code])->toBe([0, null]);
        expect($coupon->redemptions()->count())->toBe(0);
    });

    it('refuses codes this customer cannot use, and takes nothing', function () {
        Coupon::factory()->create(['code' => 'WELCOME', 'first_order_only' => true]);
        $variant = listedProduct(stockByColor: ['navy' => ['56' => 3]])->variants()->sole();
        placeOrder([$variant->id => 1]);

        expect(fn () => placeOrder([$variant->id => 1], checkoutDetails(['couponCode' => 'WELCOME'])))
            ->toThrow(OrderNotPlacedException::class, 'The code WELCOME is for first orders only. Remove the code to place your order.');

        expect($variant->refresh()->stock)->toBe(2);
        $this->assertDatabaseCount('orders', 1);
    });

    it('lets only one order take the last use of a code', function () {
        Coupon::factory()->create(['code' => 'ONCE', 'max_uses' => 1]);
        $variant = listedProduct(stockByColor: ['navy' => ['56' => 3]])->variants()->sole();
        placeOrder([$variant->id => 1], checkoutDetails(['couponCode' => 'ONCE']));

        expect(fn () => placeOrder([$variant->id => 1], checkoutDetails(['couponCode' => 'ONCE', 'phone' => QatarPhone::tryParse('6666 1234')])))
            ->toThrow(OrderNotPlacedException::class, 'The code ONCE has been fully used.');

        $this->assertDatabaseCount('orders', 1);
    });
});

describe('orders the team enters by hand', function () {
    it('starts them confirmed and records who entered them', function () {
        $user = User::factory()->staff()->create(['name' => 'Mariam']);
        $variant = listedProduct(stockByColor: ['navy' => ['56' => 3]])->variants()->sole();

        $order = placeOrder([$variant->id => 1], checkoutDetails(['channel' => OrderChannel::WhatsApp]), placedBy: $user);

        expect([$order->status, $order->channel])->toBe([OrderStatus::Confirmed, OrderChannel::WhatsApp]);
        $history = $order->statusHistories()->sole();
        expect([$history->from_status, $history->to_status, $history->user_id])->toBe([null, OrderStatus::Confirmed, $user->id]);
        expect($history->note)->toBe('Entered by hand from WhatsApp.');
        expect(StockMovement::query()->sole()->user_id)->toBe($user->id);
        expect($variant->refresh()->stock)->toBe(2);
    });

    it('charges the delivery fee the team agreed', function () {
        $variant = listedProduct(['price' => 18500])->variants()->sole();

        $order = placeOrder([$variant->id => 1], checkoutDetails(['deliveryFee' => 0]), placedBy: User::factory()->staff()->create());

        expect([$order->delivery_fee, $order->grand_total])->toBe([0, 18500]);
    });

    it('accepts phone numbers blocked from ordering online', function () {
        Customer::factory()->blocked()->create(['phone' => '+97455551234']);
        $variant = listedProduct()->variants()->sole();

        placeOrder([$variant->id => 1], placedBy: User::factory()->staff()->create());

        $this->assertDatabaseCount('orders', 1);
    });

    it('lets the team order past the per-item limit', function () {
        app(Settings::class)->set(['max_quantity_per_item' => 3]);
        $variant = listedProduct(stockByColor: ['navy' => ['56' => 20]])->variants()->sole();

        $order = placeOrder([$variant->id => 12], placedBy: User::factory()->staff()->create());

        expect($order->items()->sole()->quantity)->toBe(12);
    });

    it('does not alert the team about orders they entered themselves', function () {
        app(Settings::class)->set(['order_alert_email' => 'orders@example.com']);
        $variant = listedProduct()->variants()->sole();

        placeOrder([$variant->id => 1], checkoutDetails(['email' => 'fatima@example.com']), placedBy: User::factory()->staff()->create());

        Mail::assertQueued(OrderPlaced::class);
        Mail::assertNotQueued(NewOrderAlert::class);
    });
});
