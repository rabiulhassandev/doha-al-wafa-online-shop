<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;

/**
 * Get the only variant of a listed product.
 */
function bagVariant(array $attributes = [], int $stock = 5): ProductVariant
{
    return listedProduct($attributes, ['navy' => ['56' => $stock]])->variants()->sole();
}

describe('store', function () {
    it('adds to a new bag and answers the slide-in bag with its contents', function () {
        $variant = bagVariant(['name_en' => 'Classic crepe jelabiya']);

        $response = $this->postJson(route('cart.items.store', ['locale' => 'en']), ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('message', 'Added to your bag.')
            ->assertCookie(CartService::COOKIE);

        expect($response->json('drawer'))->toContain('Classic crepe jelabiya');
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 2]);
    });

    it('adds to the bag in the shopper’s cookie', function () {
        $item = CartItem::factory()->for(bagVariant())->create(['quantity' => 1]);

        $this->withCredentials()->withCookie(CartService::COOKIE, $item->cart->token)
            ->postJson(route('cart.items.store', ['locale' => 'en']), ['variant_id' => $item->product_variant_id])
            ->assertJsonPath('count', 2);

        $this->assertDatabaseCount('carts', 1);
        expect($item->refresh()->quantity)->toBe(2);
    });

    it('explains when fewer can be added than asked for', function () {
        $variant = bagVariant(stock: 2);

        $this->postJson(route('cart.items.store', ['locale' => 'en']), ['variant_id' => $variant->id, 'quantity' => 5])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('message', 'Only 2 of this size can be ordered, so your bag has 2.');
    });

    it('rejects sold out sizes in the shopper’s language', function () {
        $variant = bagVariant(stock: 0);

        $this->postJson(route('cart.items.store', ['locale' => 'ar']), ['variant_id' => $variant->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variant_id' => 'عذراً، نفد هذا المقاس للتو.']);

        $this->assertDatabaseCount('cart_items', 0);
    });

    it('sends plain form posts to the bag page', function () {
        $variant = bagVariant();

        $this->post(route('cart.items.store', ['locale' => 'en']), ['variant_id' => $variant->id])
            ->assertRedirectToRoute('cart.show', ['locale' => 'en'])
            ->assertSessionHas('status', 'Added to your bag.');
    });
});

describe('update', function () {
    it('changes the quantity', function () {
        $item = CartItem::factory()->for(bagVariant(stock: 5))->create(['quantity' => 1]);

        $this->withCredentials()->withCookie(CartService::COOKIE, $item->cart->token)
            ->patchJson(route('cart.items.update', ['locale' => 'en', 'item' => $item]), ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('count', 3)
            ->assertJsonPath('message', 'Your bag was updated.');

        expect($item->refresh()->quantity)->toBe(3);
    });

    it('returns 404 for a line in someone else’s bag', function () {
        $item = CartItem::factory()->for(bagVariant())->create(['quantity' => 1]);
        $myCart = Cart::factory()->create();

        $this->withCredentials()->withCookie(CartService::COOKIE, $myCart->token)
            ->patchJson(route('cart.items.update', ['locale' => 'en', 'item' => $item]), ['quantity' => 5])
            ->assertNotFound();

        expect($item->refresh()->quantity)->toBe(1);
    });
});

describe('destroy', function () {
    it('removes the line', function () {
        $item = CartItem::factory()->for(bagVariant())->create();

        $this->withCredentials()->withCookie(CartService::COOKIE, $item->cart->token)
            ->deleteJson(route('cart.items.destroy', ['locale' => 'en', 'item' => $item]))
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('message', 'Removed from your bag.');

        $this->assertModelMissing($item);
    });

    it('returns 404 without the bag cookie', function () {
        $item = CartItem::factory()->for(bagVariant())->create();

        $this->deleteJson(route('cart.items.destroy', ['locale' => 'en', 'item' => $item]))->assertNotFound();

        $this->assertModelExists($item);
    });
});
