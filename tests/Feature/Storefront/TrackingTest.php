<?php

use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Services\CartService;
use App\Services\Settings;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Get the tracking events a page hands to the browser.
 *
 * @return list<array<string, mixed>>
 */
function trackedEvents(TestResponse $response): array
{
    preg_match_all('#<script type="application/json" data-track-event>(.*?)</script>#s', $response->getContent(), $matches);

    return array_map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
}

/**
 * Get the pixel settings the page hands to the browser, or null when there are none.
 *
 * @return array<string, mixed>|null
 */
function trackingConfig(TestResponse $response): ?array
{
    return preg_match('#<script type="application/json" id="tracking-config">(.*?)</script>#s', $response->getContent(), $match) === 1
        ? json_decode($match[1], true, flags: JSON_THROW_ON_ERROR)
        : null;
}

function withPixels(bool $consentBar = false): void
{
    app(Settings::class)->set([
        'meta_pixel_id' => '1234567890123456',
        'tiktok_pixel_id' => 'C1A2B3C4D5E6F7G8H9I0',
        'consent_bar_enabled' => $consentBar,
    ]);
}

/**
 * Check out a one-piece bag and return the thank-you page address.
 */
function checkOutOnePiece(): string
{
    $item = CartItem::factory()->for(listedProduct(['price' => 18500])->variants()->sole())->create(['quantity' => 1]);

    return test()->withCookie(CartService::COOKIE, $item->cart->token)
        ->post(route('checkout.store', ['locale' => 'en']), [
            'name' => 'Fatima Al-Kuwari',
            'phone' => '5555 1234',
            'delivery_zone_id' => DeliveryZone::factory()->create(['fee' => 0])->id,
            'zone_number' => '45',
            'street_number' => '330',
            'building_number' => '12',
            'payment_method' => 'cash_on_delivery',
            'expected_subtotal' => 18500,
            'checkout_token' => (string) Str::uuid(),
            'website' => '',
        ])
        ->assertRedirect()
        ->headers->get('Location');
}

describe('pixel settings', function () {
    it('loads nothing when no pixel is set up', function () {
        $product = listedProduct();

        $response = $this->get(route('products.show', ['locale' => 'en', 'product' => $product]))->assertOk();

        expect(trackingConfig($response))->toBeNull();
        expect(trackedEvents($response))->toBe([]);
    });

    it('hands the pixel IDs to the browser and says whether to wait for consent', function (bool $consentBar) {
        withPixels($consentBar);

        $config = trackingConfig($this->get(route('home', ['locale' => 'en'])));

        expect($config)->toBe([
            'pixels' => ['meta' => '1234567890123456', 'tiktok' => 'C1A2B3C4D5E6F7G8H9I0'],
            'consentRequired' => $consentBar,
        ]);
    })->with(['consent bar on' => true, 'consent bar off' => false]);
});

describe('events', function () {
    it('sends the product view with the shown color’s sizes', function () {
        withPixels();
        $product = listedProduct(['name_en' => 'Classic crepe jelabiya', 'price' => 18500], ['navy' => ['56' => 3, '58' => 1], 'olive' => ['56' => 2]]);
        $oliveSku = $product->variants()->whereHas('productColor.color', fn ($query) => $query->where('slug', 'olive'))->value('sku');

        $events = trackedEvents($this->get(route('products.show', ['locale' => 'en', 'product' => $product, 'color' => 'olive'])));

        expect($events)->toHaveCount(1);
        expect($events[0])->toMatchArray([
            'event' => 'ViewContent',
            'currency' => 'QAR',
            'value' => 185,
            'content_name' => 'Classic crepe jelabiya',
            'contents' => [['id' => $oliveSku, 'quantity' => 1, 'price' => 185]],
        ]);
        expect($events[0]['id'])->toStartWith('viewcontent-');
    });

    it('sends what was added to the bag with the bag’s answer', function () {
        withPixels();
        $variant = listedProduct(['price' => 18500], ['navy' => ['56' => 5]])->variants()->sole();

        $response = $this->postJson(route('cart.items.store', ['locale' => 'en']), ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertOk();

        expect($response->json('tracking'))->toMatchArray([
            'event' => 'AddToCart',
            'value' => 370,
            'contents' => [['id' => $variant->sku, 'quantity' => 2, 'price' => 185]],
        ]);
    });

    it('counts only the pieces added when the bag already had some', function () {
        withPixels();
        $item = CartItem::factory()->for(listedProduct(['price' => 18500], ['navy' => ['56' => 3]])->variants()->sole())->create(['quantity' => 2]);

        $response = $this->withCredentials()->withCookie(CartService::COOKIE, $item->cart->token)
            ->postJson(route('cart.items.store', ['locale' => 'en']), ['variant_id' => $item->product_variant_id, 'quantity' => 5])
            ->assertOk();

        expect($response->json('tracking.contents.0.quantity'))->toBe(1);
    });

    it('leaves tracking out of the bag’s answer when no pixel is set up', function () {
        $variant = listedProduct()->variants()->sole();

        $this->postJson(route('cart.items.store', ['locale' => 'en']), ['variant_id' => $variant->id])
            ->assertOk()
            ->assertJsonMissingPath('tracking');
    });

    it('sends the checkout start with the bag’s total', function () {
        withPixels();
        $item = CartItem::factory()->for(listedProduct(['price' => 18500])->variants()->sole())->create(['quantity' => 2]);

        $events = trackedEvents($this->withCookie(CartService::COOKIE, $item->cart->token)->get(route('checkout.show', ['locale' => 'en'])));

        expect($events[0])->toMatchArray([
            'event' => 'InitiateCheckout',
            'contents' => [['id' => $item->productVariant->sku, 'quantity' => 2, 'price' => 185]],
        ]);
    });

    it('sends the purchase once, right after checkout, with the order’s event ID', function () {
        withPixels();

        $thankYouUrl = checkOutOnePiece();
        $order = Order::query()->sole();

        $events = trackedEvents($this->get($thankYouUrl)->assertOk());
        expect($events)->toHaveCount(1);
        expect($events[0])->toMatchArray([
            'event' => 'Purchase',
            'id' => 'purchase-'.$order->number,
            'order_id' => $order->number,
            'value' => $order->grand_total / 100,
        ]);

        expect(trackedEvents($this->get($thankYouUrl)->assertOk()))->toBe([]);
    });
});

describe('link previews', function () {
    it('previews the color that was open, with its photo', function () {
        $product = listedProduct(['name_en' => 'Classic crepe jelabiya', 'price' => 18500], ['navy' => ['56' => 3], 'olive' => ['56' => 0]]);
        $olive = $product->colors()->whereHas('color', fn ($query) => $query->where('slug', 'olive'))->with('images')->sole();

        $this->get(route('products.show', ['locale' => 'en', 'product' => $product, 'color' => 'olive']))
            ->assertOk()
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:title" content="Classic crepe jelabiya – Olive">', false)
            ->assertSee('<meta property="og:url" content="'.e(route('products.show', ['locale' => 'en', 'product' => $product, 'color' => 'olive'])).'">', false)
            ->assertSee('<meta property="og:image" content="'.e($olive->images->first()->url(1200)).'">', false)
            ->assertSee('<meta property="product:availability" content="out of stock">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    });

    it('describes other pages with the store name and language', function () {
        $this->get(route('home', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('<meta property="og:site_name" content="Doha AL WAFA">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('<meta property="og:locale" content="ar_AR">', false)
            ->assertSee('<meta property="og:locale:alternate" content="en_US">', false);
    });
});

describe('floating WhatsApp button', function () {
    it('shows when it is on and a WhatsApp number is set', function () {
        app(Settings::class)->set(['whatsapp_number' => '+97455551234', 'whatsapp_button_enabled' => true]);

        $this->get(route('home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('data-whatsapp-button', false)
            ->assertSee('https://wa.me/97455551234?text=', false);
    });

    it('stays hidden when it is off or there is no number', function (array $settings) {
        app(Settings::class)->set($settings);

        $this->get(route('home', ['locale' => 'en']))->assertDontSee('data-whatsapp-button', false);
    })->with([
        'turned off' => [['whatsapp_number' => '+97455551234', 'whatsapp_button_enabled' => false]],
        'no number' => [['whatsapp_number' => null, 'whatsapp_button_enabled' => true]],
    ]);

    it('leaves the product page to its own WhatsApp button', function () {
        app(Settings::class)->set(['whatsapp_number' => '+97455551234', 'whatsapp_button_enabled' => true]);

        $this->get(route('products.show', ['locale' => 'en', 'product' => listedProduct()]))
            ->assertOk()
            ->assertDontSee('data-whatsapp-button', false);
    });
});
