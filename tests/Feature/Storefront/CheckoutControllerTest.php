<?php

use App\Http\Controllers\Storefront\BuyNowController;
use App\Models\CartItem;
use App\Services\CartService;

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
