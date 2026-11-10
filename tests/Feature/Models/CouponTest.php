<?php

use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Support\QatarPhone;

/**
 * Record a use of the coupon on a new order with the given status.
 */
function useCoupon(Coupon $coupon, string $phone = '+97455551234', OrderStatus $status = OrderStatus::PendingConfirmation): Order
{
    $order = Order::factory()->create(['customer_phone' => $phone, 'status' => $status, 'coupon_id' => $coupon->id]);
    $coupon->redemptions()->create(['order_id' => $order->id, 'phone' => $phone, 'discount' => 1000]);

    return $order;
}

it('works out the discount on a QAR 185 bag for each type', function (string $type, int $value, ?int $deliveryFee, int $expected) {
    $coupon = new Coupon(['type' => $type, 'value' => $value]);

    expect($coupon->discountFor(18500, $deliveryFee))->toBe($expected);
})->with([
    '10% off, rounded down to the dirham' => ['percent', 10, 2500, 1850],
    'QAR 20 off' => ['fixed', 2000, 2500, 2000],
    'more off than the bag is worth' => ['fixed', 50000, 2500, 18500],
    'free delivery' => ['free_delivery', 0, 2500, 2500],
    'free delivery before choosing an area' => ['free_delivery', 0, null, 0],
]);

it('explains when a code cannot be used by anyone', function (array $attributes, string $problem) {
    $this->travelTo('2026-11-10 12:00:00');
    $coupon = Coupon::factory()->create(['code' => 'EID10', ...$attributes]);

    expect($coupon->availabilityProblem())->toBe($problem);
})->with([
    'turned off' => [['is_active' => false], 'The code EID10 is not valid.'],
    'not started' => [['starts_at' => '2026-11-11 00:00:00'], 'The code EID10 is not active yet.'],
    'expired' => [['ends_at' => '2026-11-10 11:59:00'], 'The code EID10 has expired.'],
]);

it('counts uses towards the limit, except on cancelled orders', function () {
    $coupon = Coupon::factory()->create(['code' => 'EID10', 'max_uses' => 2]);
    useCoupon($coupon, '+97455551111');
    useCoupon($coupon, '+97455552222', OrderStatus::Cancelled);

    expect($coupon->availabilityProblem())->toBeNull();

    useCoupon($coupon, '+97455553333');

    expect($coupon->availabilityProblem())->toBe('The code EID10 has been fully used.');
});

it('asks for the rest of the minimum spend', function () {
    $coupon = Coupon::factory()->create(['code' => 'EID10', 'min_subtotal' => 30000]);

    expect($coupon->problemFor(18500))->toBe('Add QAR 115 more to use the code EID10.');
    expect($coupon->problemFor(30000))->toBeNull();
});

it('keeps first-order codes for numbers that never ordered', function () {
    $coupon = Coupon::factory()->create(['code' => 'WELCOME', 'first_order_only' => true]);
    $returning = Order::factory()->create(['customer_phone' => '+97455551234']);
    Order::factory()->create(['customer_phone' => '+97466661234', 'status' => OrderStatus::Cancelled]);

    expect($coupon->problemForPhone(QatarPhone::tryParse('5555 1234')))->toBe('The code WELCOME is for first orders only.');
    expect($coupon->problemForPhone(QatarPhone::tryParse('6666 1234')))->toBeNull();
    expect($coupon->problemForPhone(QatarPhone::tryParse('7777 1234')))->toBeNull();
    expect($returning->exists)->toBeTrue();
});

it('limits uses per mobile number', function () {
    $coupon = Coupon::factory()->create(['code' => 'EID10', 'max_uses_per_phone' => 1]);
    $order = useCoupon($coupon, '+97455551234');

    expect($coupon->problemForPhone(QatarPhone::tryParse('5555 1234')))->toBe('You have already used the code EID10.');

    app(UpdateOrderStatus::class)->handle($order, OrderStatus::Cancelled, null, 'Changed mind');

    expect($coupon->problemForPhone(QatarPhone::tryParse('5555 1234')))->toBeNull();
});

it('finds codes however they are typed', function () {
    $coupon = Coupon::factory()->create(['code' => 'EID10']);

    expect(Coupon::findByCode(' eid 10 ')?->is($coupon))->toBeTrue();
    expect(Coupon::findByCode('EID11'))->toBeNull();
});
