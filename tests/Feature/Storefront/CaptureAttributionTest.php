<?php

use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\ShortLink;
use App\Services\CartService;
use App\Support\Attribution;
use Illuminate\Support\Str;

/**
 * Place an order from a one-piece bag, with the attribution cookie the browser kept.
 */
function placeOrderWithAttributionCookie(?string $attributionCookie): Order
{
    $item = CartItem::factory()
        ->for(listedProduct(['price' => 18500])->variants()->sole())
        ->create(['quantity' => 1]);
    $zone = DeliveryZone::factory()->create();

    $request = test()->withCookie(CartService::COOKIE, $item->cart->token);

    if ($attributionCookie !== null) {
        $request->withCookie(Attribution::COOKIE, $attributionCookie);
    }

    $request->post(route('checkout.store', ['locale' => 'en']), [
        'name' => 'Fatima Al-Kuwari',
        'phone' => '5555 1234',
        'delivery_zone_id' => $zone->id,
        'zone_number' => '45',
        'street_number' => '330',
        'building_number' => '12',
        'payment_method' => 'cash_on_delivery',
        'expected_subtotal' => 18500,
        'checkout_token' => (string) Str::uuid(),
        'website' => '',
    ])->assertRedirect();

    return Order::query()->sole();
}

it('remembers campaign tags and ad click IDs for 30 days', function () {
    $response = $this->get(route('home', [
        'locale' => 'ar',
        'utm_source' => 'tiktok',
        'utm_medium' => 'paid',
        'utm_campaign' => 'eid-2026',
        'ttclid' => 'E.C.P.abc123',
    ]))->assertOk();

    $cookie = $response->getCookie(Attribution::COOKIE);
    expect($cookie)->not->toBeNull();
    expect($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(29)->getTimestamp());

    $attribution = Attribution::fromCookie($cookie->getValue());
    expect($attribution->utm)->toBe(['utm_source' => 'tiktok', 'utm_medium' => 'paid', 'utm_campaign' => 'eid-2026']);
    expect([$attribution->clickIdType, $attribution->clickId])->toBe(['tiktok', 'E.C.P.abc123']);
});

it('keeps the earlier source on direct visits and visits from within the store', function () {
    $this->get(route('home', ['locale' => 'en']))->assertCookieMissing(Attribution::COOKIE);
    $this->get(route('shop', ['locale' => 'en']), ['Referer' => route('home', ['locale' => 'en'])])
        ->assertCookieMissing(Attribution::COOKIE);
});

it('remembers visits from other sites', function () {
    $this->get(route('home', ['locale' => 'en']), ['Referer' => 'https://l.instagram.com/?u=x'])
        ->assertCookie(Attribution::COOKIE);
});

it('saves where the shopper came from on the order', function () {
    $shortLink = ShortLink::factory()->create(['code' => 'eid-sara']);
    $attribution = new Attribution(
        utm: ['utm_source' => 'instagram', 'utm_medium' => 'influencer', 'utm_campaign' => 'eid-2026'],
        clickIdType: 'meta',
        clickId: 'IwAR0abc',
        referrer: 'https://l.instagram.com/',
        landingUrl: 'http://localhost/ar/products/classic?sl=eid-sara',
        shortLinkId: $shortLink->id,
    );

    $order = placeOrderWithAttributionCookie($attribution->toCookieValue());

    expect($order->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'click_id_type', 'click_id', 'referrer', 'short_link_id']))->toBe([
        'utm_source' => 'instagram',
        'utm_medium' => 'influencer',
        'utm_campaign' => 'eid-2026',
        'utm_content' => null,
        'click_id_type' => 'meta',
        'click_id' => 'IwAR0abc',
        'referrer' => 'https://l.instagram.com/',
        'short_link_id' => $shortLink->id,
    ]);
    expect($order->sourceLabel())->toBe('instagram / influencer');
});

it('places the order without a source when there is none or the cookie is damaged', function (?string $cookie) {
    $order = placeOrderWithAttributionCookie($cookie);

    expect([$order->utm_source, $order->click_id, $order->short_link_id])->toBe([null, null, null]);
})->with([
    'no cookie' => [null],
    'damaged cookie' => ['{not json'],
]);

it('follows a short link through to the order', function () {
    ShortLink::factory()->create([
        'code' => 'bio',
        'destination' => '/en',
        'utm_source' => 'instagram',
        'utm_medium' => 'bio',
    ]);

    $target = $this->get('/p/bio')->headers->get('Location');
    $attributionCookie = $this->get($target)->assertOk()->getCookie(Attribution::COOKIE)->getValue();

    $order = placeOrderWithAttributionCookie($attributionCookie);

    expect($order->shortLink->code)->toBe('bio');
    expect([$order->utm_source, $order->utm_medium])->toBe(['instagram', 'bio']);
});
