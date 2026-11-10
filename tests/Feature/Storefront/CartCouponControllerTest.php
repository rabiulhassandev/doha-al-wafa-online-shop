<?php

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Services\CartService;

/**
 * Put one QAR 185 piece in a new bag.
 */
function couponBag(): CartItem
{
    return CartItem::factory()->for(listedProduct(['price' => 18500])->variants()->sole())->create(['quantity' => 1]);
}

it('applies a code typed in the bag and shows the discount', function () {
    Coupon::factory()->create(['code' => 'EID10', 'value' => 10]);
    $item = couponBag();

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->from(route('cart.show', ['locale' => 'en']))
        ->post(route('cart.coupon.store', ['locale' => 'en']), ['code' => ' eid10 '])
        ->assertRedirect(route('cart.show', ['locale' => 'en']))
        ->assertSessionHas(CartService::COUPON_SESSION_KEY, 'EID10')
        ->assertSessionHas('coupon_notice', 'The code EID10 is applied: 10% off.');

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->get(route('cart.show', ['locale' => 'en']))
        ->assertSee('Discount')
        ->assertSee('−QAR 18.50')
        ->assertSee('QAR 166.50');
});

it('explains codes that cannot be used, in the shopper’s language', function (array $attributes, string $locale, string $message) {
    Coupon::factory()->create(['code' => 'EID10', ...$attributes]);

    $this->from(route('cart.show', ['locale' => $locale]))
        ->post(route('cart.coupon.store', ['locale' => $locale]), ['code' => 'EID10'])
        ->assertSessionHasErrors(['code' => $message], errorBag: 'coupon')
        ->assertSessionMissing(CartService::COUPON_SESSION_KEY);
})->with([
    'turned off' => [['is_active' => false], 'en', 'The code EID10 is not valid.'],
    'expired, in Arabic' => [['ends_at' => '2020-01-01 00:00:00'], 'ar', 'انتهت صلاحية الكود EID10.'],
]);

it('rejects unknown codes', function () {
    $this->post(route('cart.coupon.store', ['locale' => 'en']), ['code' => 'NOPE'])
        ->assertSessionHasErrors(['code' => 'The code NOPE is not valid.'], errorBag: 'coupon');
});

it('removes the applied code', function () {
    session([CartService::COUPON_SESSION_KEY => 'EID10']);

    $this->delete(route('cart.coupon.destroy', ['locale' => 'en']))
        ->assertSessionMissing(CartService::COUPON_SESSION_KEY);
});

it('applies a code from a shared link', function () {
    Coupon::factory()->create(['code' => 'SARA15', 'value' => 15]);

    $this->get(route('home', ['locale' => 'ar', 'coupon' => 'sara15']))
        ->assertOk()
        ->assertSee('تم تطبيق الكود SARA15: خصم 15%.')
        ->assertSessionHas(CartService::COUPON_SESSION_KEY, 'SARA15');
});

it('ignores links with codes that cannot be used', function () {
    Coupon::factory()->create(['code' => 'OLD', 'is_active' => false]);

    $this->get(route('home', ['locale' => 'en', 'coupon' => 'OLD']))
        ->assertOk()
        ->assertSessionMissing(CartService::COUPON_SESSION_KEY);
});

it('shows how much more to spend before a code applies', function () {
    Coupon::factory()->create(['code' => 'BIG50', 'min_subtotal' => 30000]);
    $item = couponBag();

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->withSession([CartService::COUPON_SESSION_KEY => 'BIG50'])
        ->get(route('cart.show', ['locale' => 'en']))
        ->assertSee('Add QAR 115 more to use the code BIG50.')
        ->assertSee('QAR 185');
});

it('takes the delivery fee off with a free delivery code at checkout', function () {
    Coupon::factory()->freeDelivery()->create(['code' => 'FREESHIP']);
    $zone = DeliveryZone::factory()->create(['name_en' => 'Al Wakrah', 'fee' => 2500]);
    $item = couponBag();

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->withSession([CartService::COUPON_SESSION_KEY => 'FREESHIP', CartService::DELIVERY_ZONE_SESSION_KEY => $zone->id])
        ->get(route('checkout.show', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('FREESHIP')
        ->assertSee('−QAR 25')
        ->assertSee('form="coupon-remove-form"', false);
});
