<?php

use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Services\CartService;
use App\Services\Settings;

it('shows the bag with its lines and totals', function () {
    $product = listedProduct(['name_en' => 'Classic crepe jelabiya', 'price' => 18500]);
    $item = CartItem::factory()->for($product->variants()->sole())->create(['quantity' => 2]);

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->get(route('cart.show', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Classic crepe jelabiya')
        ->assertSee('QAR 370')
        ->assertSee('Choose your area');
});

it('shows an empty bag', function () {
    $this->get(route('cart.show', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Your bag is empty.');
});

it('shows when a quantity was lowered to the stock left', function () {
    $product = listedProduct(stockByColor: ['navy' => ['56' => 1]]);
    $item = CartItem::factory()->for($product->variants()->sole())->create(['quantity' => 3]);

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->get(route('cart.show', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Only 1 left, so we lowered the quantity.');
});

it('remembers the delivery area and shows its fee', function () {
    app(Settings::class)->set(['delivery_free_over' => 50000]);
    $zone = DeliveryZone::factory()->create(['name_en' => 'Al Wakrah', 'fee' => 2500]);
    $item = CartItem::factory()->for(listedProduct(['price' => 18500])->variants()->sole())->create();

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->from(route('cart.show', ['locale' => 'en']))
        ->put(route('cart.delivery-zone.update', ['locale' => 'en']), ['delivery_zone_id' => $zone->id])
        ->assertRedirectToRoute('cart.show', ['locale' => 'en'])
        ->assertSessionHas(CartService::DELIVERY_ZONE_SESSION_KEY, $zone->id);

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->get(route('cart.show', ['locale' => 'en']))
        ->assertSee('Al Wakrah')
        ->assertSee('QAR 25')
        ->assertSee('QAR 210')
        ->assertSee('Add QAR 315 more for free delivery.');
});

it('rejects paused delivery areas', function () {
    $paused = DeliveryZone::factory()->paused()->create();

    $this->put(route('cart.delivery-zone.update', ['locale' => 'en']), ['delivery_zone_id' => $paused->id])
        ->assertSessionHasErrors(['delivery_zone_id' => 'Choose your area.']);
});

it('renders the slide-in bag on its own', function () {
    $item = CartItem::factory()->for(listedProduct(['name_en' => 'Embroidered kaftan'])->variants()->sole())->create();

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->get(route('cart.drawer', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Embroidered kaftan')
        ->assertDontSee('<html', false);
});

it('shows the bag count in the header', function () {
    $item = CartItem::factory()->for(listedProduct()->variants()->sole())->create(['quantity' => 3]);

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->get(route('home', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('data-count="3"', false);
});
