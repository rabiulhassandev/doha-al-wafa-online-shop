<?php

use App\Http\Controllers\Storefront\BuyNowController;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Services\CartService;
use App\Services\Settings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Build a complete checkout form submission for the given delivery area.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function checkoutPayload(DeliveryZone $zone, array $overrides = []): array
{
    return [
        'name' => 'Fatima Al-Kuwari',
        'phone' => '5555 1234',
        'email' => '',
        'delivery_zone_id' => $zone->id,
        'zone_number' => '45',
        'street_number' => '330',
        'building_number' => '12',
        'unit' => '',
        'landmark' => '',
        'latitude' => '',
        'longitude' => '',
        'preferred_delivery_date' => '',
        'note' => '',
        'payment_method' => 'cash_on_delivery',
        'expected_subtotal' => 18500,
        'checkout_token' => (string) Str::uuid(),
        'website' => '',
        ...$overrides,
    ];
}

/**
 * Put one QAR 185 piece in a new bag.
 */
function bagWithOnePiece(int $stock = 3): CartItem
{
    return CartItem::factory()
        ->for(listedProduct(['name_en' => 'Classic crepe jelabiya', 'price' => 18500], ['navy' => ['56' => $stock]])->variants()->sole())
        ->create(['quantity' => 1]);
}

/**
 * Submit the checkout form with the bag's cookie.
 *
 * @param  array<string, mixed>  $payload
 */
function submitCheckout(CartItem $item, array $payload, string $locale = 'en'): TestResponse
{
    return test()->withCookie(CartService::COOKIE, $item->cart->token)
        ->post(route('checkout.store', ['locale' => $locale]), $payload);
}

it('sells a product by size without displaying a color', function () {
    $product = Product::factory()->active()->create(['name_en' => 'Classic jelabiya', 'price' => 18500]);
    $gallery = ProductColor::factory()->for($product)->create(['color_id' => null, 'is_default' => true]);
    ProductImage::factory()->for($gallery)->create();
    $size = Size::factory()->create(['slug' => '56', 'name_en' => '56']);
    $variant = ProductVariant::factory()->for($gallery)->for($size)->inStock(3)->create();
    $item = CartItem::factory()->for($variant)->create(['quantity' => 1]);

    $this->get('/en/shop')->assertOk()->assertSee('Classic jelabiya')->assertDontSee('name="color[]"', false);
    $this->get(route('products.show', ['locale' => 'en', 'product' => $product, 'size' => '56']))
        ->assertOk()
        ->assertSee('BANAT AL WAFA')
        ->assertSee('Choose a size')
        ->assertDontSee('selectColor(', false)
        ->assertDontSee('schema.org/color');
    $this->withCookie(CartService::COOKIE, $item->cart->token)->get('/en/checkout')->assertOk()->assertSee('Size 56');
    $response = submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create()));

    $order = Order::query()->sole();
    $response->assertRedirect($order->confirmationUrl());
    $this->get($order->confirmationUrl())->assertOk()->assertSee('Size 56')->assertDontSee('background-color: "', false);
    expect($order->items()->sole()->color_name_en)->toBe('');
    expect($variant->fresh()->stock)->toBe(2);
});

it('goes straight to checkout with only the "Order now" item', function () {
    $inBag = CartItem::factory()->for(listedProduct(['name_en' => 'Already in bag'])->variants()->sole())->create();
    $variant = listedProduct(['name_en' => 'Ordered now'])->variants()->sole();

    $this->withCookie(CartService::COOKIE, $inBag->cart->token)
        ->post(route('checkout.buy-now', ['locale' => 'en']), ['variant_id' => $variant->id, 'quantity' => 2])
        ->assertRedirectToRoute('checkout.show', ['locale' => 'en'])
        ->assertSessionHas(BuyNowController::SESSION_KEY, ['variant_id' => $variant->id, 'quantity' => 2]);

    $this->withCookie(CartService::COOKIE, $inBag->cart->token)
        ->get(route('checkout.show', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Ordered now')
        ->assertDontSee('Already in bag')
        ->assertSee('You are ordering this item only.');

    $this->assertModelExists($inBag);
});

it('orders the bag when opened from the bag', function () {
    $inBag = CartItem::factory()->for(listedProduct(['name_en' => 'Already in bag'])->variants()->sole())->create();
    session([BuyNowController::SESSION_KEY => ['variant_id' => listedProduct(['name_en' => 'Earlier order now'])->variants()->sole()->id, 'quantity' => 1]]);

    $this->withCookie(CartService::COOKIE, $inBag->cart->token)
        ->get(route('checkout.show', ['locale' => 'en', 'from' => 'bag']))
        ->assertOk()
        ->assertSee('Already in bag')
        ->assertDontSee('Earlier order now')
        ->assertSessionMissing(BuyNowController::SESSION_KEY);
});

it('limits "Order now" to the stock left', function () {
    $variant = listedProduct(stockByColor: ['navy' => ['56' => 2]])->variants()->sole();

    $this->post(route('checkout.buy-now', ['locale' => 'en']), ['variant_id' => $variant->id, 'quantity' => 9])
        ->assertSessionHas(BuyNowController::SESSION_KEY, ['variant_id' => $variant->id, 'quantity' => 2]);
});

it('refuses "Order now" for a sold out size', function () {
    $variant = listedProduct(stockByColor: ['navy' => ['56' => 0]])->variants()->sole();

    $this->post(route('checkout.buy-now', ['locale' => 'en']), ['variant_id' => $variant->id])
        ->assertSessionHasErrors(['variant_id' => 'Sorry, this size just sold out.'])
        ->assertSessionMissing(BuyNowController::SESSION_KEY);
});

it('sends shoppers with nothing to order back to the bag', function () {
    $this->get(route('checkout.show', ['locale' => 'en']))
        ->assertRedirectToRoute('cart.show', ['locale' => 'en']);
});

it('shows the details form with each area and its fee', function () {
    DeliveryZone::factory()->create(['name_en' => 'Al Wakrah', 'fee' => 2500]);
    $item = bagWithOnePiece();

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->get(route('checkout.show', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('name="zone_number"', false)
        ->assertSee('Al Wakrah — QAR 25')
        ->assertSee('Cash on delivery')
        ->assertSee('name="expected_subtotal" value="18500"', false);
});

it('sends a sold out "Order now" item back to its product', function () {
    $product = listedProduct(stockByColor: ['navy' => ['56' => 0]]);
    session([BuyNowController::SESSION_KEY => ['variant_id' => $product->variants()->sole()->id, 'quantity' => 1]]);

    $this->get(route('checkout.show', ['locale' => 'en']))
        ->assertRedirectToRoute('products.show', ['locale' => 'en', 'product' => $product->slug])
        ->assertSessionHasErrors(['variant_id' => 'Sorry, this size just sold out.'])
        ->assertSessionMissing(BuyNowController::SESSION_KEY);
});

describe('placing an order', function () {
    beforeEach(fn () => Mail::fake());

    it('orders the bag as a guest and opens the thank-you page', function () {
        $zone = DeliveryZone::factory()->create(['fee' => 2500]);
        $item = bagWithOnePiece(stock: 3);

        $response = submitCheckout($item, checkoutPayload($zone));

        $order = Order::query()->sole();
        expect($response->headers->get('Location'))->toContain("/en/orders/{$order->number}/thank-you?expires=");
        expect([$order->customer_phone, $order->grand_total, $order->zone_number])->toBe(['+97455551234', 21000, '45']);
        expect($item->productVariant->refresh()->stock)->toBe(2);
        $this->assertModelMissing($item);
    });

    it('orders only the "Order now" item and leaves the bag alone', function () {
        $zone = DeliveryZone::factory()->create();
        $inBag = bagWithOnePiece();
        $orderedNow = listedProduct(['price' => 9000])->variants()->sole();
        session([BuyNowController::SESSION_KEY => ['variant_id' => $orderedNow->id, 'quantity' => 2]]);

        submitCheckout($inBag, checkoutPayload($zone, ['expected_subtotal' => 18000]))
            ->assertRedirect()
            ->assertSessionMissing(BuyNowController::SESSION_KEY);

        expect(Order::query()->sole()->items()->sole()->product_variant_id)->toBe($orderedNow->id);
        $this->assertModelExists($inBag);
    });

    it('explains mistakes in the shopper’s language', function () {
        $item = bagWithOnePiece();

        submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create(), ['name' => '', 'phone' => '4444 123', 'zone_number' => '']), 'ar')
            ->assertSessionHasErrors([
                'name' => 'أدخل اسمك الكامل.',
                'phone' => 'أدخل رقم هاتف قطري من 8 أرقام.',
                'zone_number' => 'أدخل رقم المنطقة من اللوحة الزرقاء.',
            ]);

        $this->assertDatabaseCount('orders', 0);
    });

    it('accepts Qatar phone numbers written in any common way', function (string $phone, string $normalized) {
        $this->freezeTime();
        $item = bagWithOnePiece();

        submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create(), ['phone' => $phone]))->assertSessionHasNoErrors();

        $order = Order::query()->sole();
        expect($order->customer_phone)->toBe($normalized);

        $this->post(route('track.lookup', ['locale' => 'en']), ['number' => $order->number, 'phone' => $phone])
            ->assertRedirect($order->confirmationUrl('en'));
    })->with([
        'local mobile' => ['5555 1234', '+97455551234'],
        'international mobile' => ['+974 5555 1234', '+97455551234'],
        'mobile with 00' => ['00974 5555 1234', '+97455551234'],
        'mobile with Arabic digits' => ['٥٥٥٥ ١٢٣٤', '+97455551234'],
        'local landline' => ['4444 1234', '+97444441234'],
        'international landline' => ['+974 4444 1234', '+97444441234'],
    ]);

    it('rejects invalid Qatar phone numbers', function (string $phone) {
        $item = bagWithOnePiece();

        submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create(), ['phone' => $phone]))
            ->assertSessionHasErrors(['phone' => 'Enter a Qatar phone number with 8 digits.']);

        $this->assertDatabaseCount('orders', 0);
    })->with([
        'too short' => '5555 123',
        'another country' => '+971 50 123 4567',
    ]);

    it('accepts blue plate numbers typed in Arabic digits', function () {
        $item = bagWithOnePiece();

        submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create(), ['zone_number' => '٤٥', 'street_number' => '٠٣٣٠']))
            ->assertSessionHasNoErrors();

        $order = Order::query()->sole();
        expect([$order->zone_number, $order->street_number])->toBe(['45', '330']);
    });

    it('turns away bots that fill the hidden field', function () {
        $item = bagWithOnePiece();

        submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create(), ['website' => 'https://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('orders', 0);
    });

    it('places one order when "Place order" is sent twice', function () {
        $item = bagWithOnePiece(stock: 3);
        $payload = checkoutPayload(DeliveryZone::factory()->create());

        $first = submitCheckout($item, $payload);
        $second = submitCheckout($item, $payload);

        $order = Order::query()->sole();
        expect($first->headers->get('Location'))->toContain("/orders/{$order->number}/thank-you");
        expect($second->headers->get('Location'))->toContain("/orders/{$order->number}/thank-you");
        expect($item->productVariant->refresh()->stock)->toBe(2);
    });

    it('asks the shopper to check the order when it changed', function () {
        $item = bagWithOnePiece();

        submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create(), ['expected_subtotal' => 15000]))
            ->assertRedirectToRoute('checkout.show', ['locale' => 'en'])
            ->assertSessionHasErrors(['order' => 'Your order changed since you opened this page. Please check it and place it again.'])
            ->assertSessionHasInput('name', 'Fatima Al-Kuwari');

        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($item);
    });

    it('accepts only a preferred date that is offered', function (string $date, bool $accepted) {
        $this->travelTo('2026-11-09 10:00:00');
        $item = bagWithOnePiece();

        $response = submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create(['eta_min_days' => 1]), ['preferred_delivery_date' => $date]));

        if ($accepted) {
            $response->assertSessionHasNoErrors();
            expect(Order::query()->sole()->preferred_delivery_date->toDateString())->toBe($date);
        } else {
            $response->assertSessionHasErrors(['preferred_delivery_date' => 'Choose one of the dates shown.']);
            $this->assertDatabaseCount('orders', 0);
        }
    })->with([
        // 2026-11-09 is a Monday and Fridays have no delivery.
        'the earliest delivery day' => ['2026-11-10', true],
        'the seventh delivery day' => ['2026-11-17', true],
        'today' => ['2026-11-09', false],
        'a Friday' => ['2026-11-13', false],
        'too far ahead' => ['2026-11-18', false],
    ]);

    it('ignores a preferred date when the store turned the choice off', function () {
        $this->travelTo('2026-11-09 10:00:00');
        app(Settings::class)->set(['preferred_date_enabled' => false]);
        $item = bagWithOnePiece();

        submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create(), ['preferred_delivery_date' => '2026-11-10']))
            ->assertSessionHasNoErrors();

        expect(Order::query()->sole()->preferred_delivery_date)->toBeNull();
    });

    it('slows down repeated attempts from one phone number', function () {
        $item = bagWithOnePiece();
        $payload = checkoutPayload(DeliveryZone::factory()->create(), ['name' => '']);

        foreach (range(1, 5) as $attempt) {
            submitCheckout($item, $payload)->assertSessionHasErrors('name');
        }

        submitCheckout($item, $payload)
            ->assertRedirectToRoute('checkout.show', ['locale' => 'en'])
            ->assertSessionHasErrors(['order' => 'Too many attempts. Please wait a few minutes and try again.']);
    });

    it('places the order with the applied code and forgets the code afterwards', function () {
        Coupon::factory()->fixed(2000)->create(['code' => 'EID20']);
        $item = bagWithOnePiece();

        $response = $this->withCookie(CartService::COOKIE, $item->cart->token)
            ->withSession([CartService::COUPON_SESSION_KEY => 'EID20'])
            ->post(route('checkout.store', ['locale' => 'en']), checkoutPayload(DeliveryZone::factory()->create(['fee' => 2500])))
            ->assertSessionMissing(CartService::COUPON_SESSION_KEY);

        $order = Order::query()->sole();
        expect([$order->discount_total, $order->grand_total, $order->coupon_code])->toBe([2000, 19000, 'EID20']);
        $this->get($response->headers->get('Location'))->assertSee('Discount')->assertSee('−QAR 20');
    });

    it('keeps the shopper’s details when a code cannot be used for them', function () {
        Coupon::factory()->create(['code' => 'WELCOME', 'first_order_only' => true]);
        Order::factory()->create(['customer_phone' => '+97455551234']);
        $item = bagWithOnePiece();

        $this->withCookie(CartService::COOKIE, $item->cart->token)
            ->withSession([CartService::COUPON_SESSION_KEY => 'WELCOME'])
            ->post(route('checkout.store', ['locale' => 'en']), checkoutPayload(DeliveryZone::factory()->create()))
            ->assertRedirectToRoute('checkout.show', ['locale' => 'en'])
            ->assertSessionHasErrors(['order' => 'The code WELCOME is for first orders only. Remove the code to place your order.'])
            ->assertSessionHasInput('building_number', '12');

        $this->assertDatabaseCount('orders', 1);
    });

    it('sends a bag with a sold out line back to the bag', function () {
        $item = bagWithOnePiece(stock: 0);

        submitCheckout($item, checkoutPayload(DeliveryZone::factory()->create()))
            ->assertRedirectToRoute('cart.show', ['locale' => 'en']);

        $this->assertDatabaseCount('orders', 0);
    });
});
