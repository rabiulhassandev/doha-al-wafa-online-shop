<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\User;

/**
 * Build a complete discount code form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function couponPayload(array $overrides = []): array
{
    return [
        'code' => 'eid10',
        'description' => 'Eid campaign',
        'type' => 'percent',
        'percent' => '10',
        'amount' => '',
        'min_subtotal' => '200',
        'starts_at' => '',
        'ends_at' => '2026-12-31T23:59',
        'max_uses' => '100',
        'max_uses_per_phone' => '1',
        'first_order_only' => '0',
        'is_active' => '1',
        ...$overrides,
    ];
}

it('adds a code in capitals with amounts in dirhams', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->post(route('admin.coupons.store'), couponPayload())
        ->assertRedirectToRoute('admin.coupons.index')
        ->assertSessionHas('status', 'The code EID10 was added.');

    $coupon = Coupon::query()->sole();
    expect([$coupon->code, $coupon->type, $coupon->value, $coupon->min_subtotal, $coupon->max_uses_per_phone])
        ->toBe(['EID10', CouponType::Percent, 10, 20000, 1]);
    expect($coupon->ends_at->format('Y-m-d H:i'))->toBe('2026-12-31 23:59');
});

it('stores a fixed amount in dirhams', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->post(route('admin.coupons.store'), couponPayload(['type' => 'fixed', 'percent' => '', 'amount' => '25.50']));

    expect(Coupon::query()->sole()->value)->toBe(2550);
});

it('checks the code and the discount', function (array $overrides, string $field) {
    Coupon::factory()->create(['code' => 'TAKEN']);

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('admin.coupons.store'), couponPayload($overrides))
        ->assertSessionHasErrors($field);
})->with([
    'code with spaces inside words' => [['code' => 'EID 10!'], 'code'],
    'code already used' => [['code' => 'taken'], 'code'],
    'no percentage' => [['percent' => ''], 'percent'],
    'over 100%' => [['percent' => '120'], 'percent'],
    'no amount' => [['type' => 'fixed', 'amount' => ''], 'amount'],
    'ends before it starts' => [['starts_at' => '2026-12-01T00:00', 'ends_at' => '2026-11-01T00:00'], 'ends_at'],
]);

it('lists codes with their uses, discount given and sales', function () {
    $coupon = Coupon::factory()->create(['code' => 'EID10', 'value' => 10]);
    $variant = listedProduct(['price' => 20000])->variants()->sole();
    placeOrder([$variant->id => 1], checkoutDetails(['couponCode' => 'EID10']));

    $this->actingAs(User::factory()->owner()->create())
        ->get(route('admin.coupons.index'))
        ->assertOk()
        ->assertSeeInOrder(['EID10', '10% off', '1', 'QAR 20', 'QAR 205', 'Working'])
        ->assertSee('/ar?coupon=EID10', false);

    expect($coupon->redemptions()->count())->toBe(1);
});

it('keeps used codes for the order history', function () {
    $coupon = Coupon::factory()->create(['code' => 'EID10']);
    placeOrder([listedProduct()->variants()->sole()->id => 1], checkoutDetails(['couponCode' => 'EID10']));

    $this->actingAs(User::factory()->owner()->create())
        ->delete(route('admin.coupons.destroy', $coupon))
        ->assertSessionHas('error');

    $this->assertModelExists($coupon);
});

it('deletes unused codes', function () {
    $coupon = Coupon::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->delete(route('admin.coupons.destroy', $coupon))
        ->assertRedirectToRoute('admin.coupons.index');

    $this->assertModelMissing($coupon);
});

it('lets only owners manage codes, pages and FAQ', function (string $route) {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route($route))
        ->assertForbidden();
})->with(['admin.coupons.index', 'admin.pages.index', 'admin.faqs.index']);
