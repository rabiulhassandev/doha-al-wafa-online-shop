<?php

use App\Models\ShortLink;

it('opens the page with its campaign tags, discount code and its own code', function () {
    $shortLink = ShortLink::factory()->create([
        'code' => 'eid-sara',
        'destination' => '/ar/products/classic-jelabiya?color=navy',
        'utm_source' => 'instagram',
        'utm_medium' => 'influencer',
        'utm_campaign' => 'eid-2026',
        'utm_content' => null,
        'coupon_code' => 'SARA10',
    ]);

    $response = $this->get('/p/eid-sara')->assertRedirect();

    $location = $response->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    expect(parse_url($location, PHP_URL_PATH))->toBe('/ar/products/classic-jelabiya');
    expect($query)->toBe([
        'color' => 'navy',
        'utm_source' => 'instagram',
        'utm_medium' => 'influencer',
        'utm_campaign' => 'eid-2026',
        'coupon' => 'SARA10',
        'sl' => 'eid-sara',
    ]);
    expect($shortLink->fresh()->clicks)->toBe(1);
});

it('finds the link whatever the letter case', function () {
    ShortLink::factory()->create(['code' => 'k7q2mx']);

    $this->get('/p/K7Q2MX')->assertRedirect();
});

it('does not count link previews as visits', function (string $userAgent) {
    $shortLink = ShortLink::factory()->create(['code' => 'k7q2mx']);

    $this->get('/p/k7q2mx', ['User-Agent' => $userAgent])->assertRedirect();

    expect($shortLink->fresh()->clicks)->toBe(0);
})->with([
    'Facebook and Instagram' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'WhatsApp' => 'WhatsApp/2.23.20.0',
    'Telegram' => 'TelegramBot (like TwitterBot)',
]);

it('counts visits from in-app browsers', function () {
    $shortLink = ShortLink::factory()->create(['code' => 'k7q2mx']);

    $this->get('/p/k7q2mx', ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Instagram 300.0'])
        ->assertRedirect();

    expect($shortLink->fresh()->clicks)->toBe(1);
});

it('shows page not found for a link that is off or unknown', function () {
    ShortLink::factory()->create(['code' => 'k7q2mx', 'is_active' => false]);

    $this->get('/p/k7q2mx')->assertNotFound();
    $this->get('/p/nothing')->assertNotFound();
});
