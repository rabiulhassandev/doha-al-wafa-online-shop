<?php

use App\Enums\CartLineStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\Settings;

/**
 * Get the only variant of a listed product.
 */
function onlyVariant(array $attributes = [], int $stock = 5): ProductVariant
{
    return listedProduct($attributes, ['navy' => ['56' => $stock]])->variants()->sole();
}

describe('add', function () {
    it('adds to the bag and merges repeated adds', function () {
        $cart = Cart::factory()->create();
        $variant = onlyVariant(stock: 5);

        app(CartService::class)->add($cart, $variant, 1);
        $result = app(CartService::class)->add($cart, $variant, 2);

        expect($result)->toBe(['quantity' => 3, 'limited' => false]);
        expect($cart->items()->sole()->quantity)->toBe(3);
    });

    it('caps the quantity at the stock left', function () {
        $cart = Cart::factory()->create();
        $variant = onlyVariant(stock: 2);

        $result = app(CartService::class)->add($cart, $variant, 5);

        expect($result)->toBe(['quantity' => 2, 'limited' => true]);
    });

    it('caps the quantity at the per-item limit', function () {
        app(Settings::class)->set(['max_quantity_per_item' => 3]);
        $cart = Cart::factory()->create();

        $result = app(CartService::class)->add($cart, onlyVariant(stock: 20), 10);

        expect($result)->toBe(['quantity' => 3, 'limited' => true]);
    });

    it('adds nothing for sizes that cannot be bought', function (Closure $makeVariant) {
        $cart = Cart::factory()->create();

        $result = app(CartService::class)->add($cart, $makeVariant(), 1);

        expect($result['quantity'])->toBe(0);
        $this->assertDatabaseCount('cart_items', 0);
    })->with([
        'sold out' => fn () => onlyVariant(stock: 0),
        'no longer sold' => fn () => tap(onlyVariant(), fn (ProductVariant $variant) => $variant->update(['is_active' => false])),
        'hidden color' => fn () => tap(onlyVariant(), fn (ProductVariant $variant) => $variant->productColor->update(['is_active' => false])),
        'draft product' => fn () => onlyVariant(['status' => 'draft']),
    ]);
});

describe('setQuantity', function () {
    it('changes the quantity within the stock left', function () {
        $item = CartItem::factory()->for(onlyVariant(stock: 4))->create(['quantity' => 1]);

        $result = app(CartService::class)->setQuantity($item, 9);

        expect($result)->toBe(['quantity' => 4, 'limited' => true]);
        expect($item->refresh()->quantity)->toBe(4);
    });

    it('removes the line at zero', function () {
        $item = CartItem::factory()->for(onlyVariant())->create(['quantity' => 2]);

        app(CartService::class)->setQuantity($item, 0);

        $this->assertModelMissing($item);
    });
});

describe('summary', function () {
    it('prices lines from the live product, including a size’s own price', function () {
        $variant = onlyVariant(['price' => 18500]);
        $item = CartItem::factory()->for($variant)->create(['quantity' => 2]);
        $variant->update(['price' => 19900]);

        $summary = app(CartService::class)->summary($item->cart, null);

        expect($summary->subtotal())->toBe(39800);
        expect($summary->itemCount())->toBe(2);
    });

    it('lowers quantities to the stock left and flags them', function () {
        $item = CartItem::factory()->for(onlyVariant(stock: 1))->create(['quantity' => 3]);

        $summary = app(CartService::class)->summary($item->cart, null);

        expect($summary->lines->sole()->status)->toBe(CartLineStatus::Reduced);
        expect($summary->lines->sole()->quantity)->toBe(1);
        expect($item->refresh()->quantity)->toBe(1);
    });

    it('leaves sold out lines out of the totals', function () {
        $soldOut = CartItem::factory()->for(onlyVariant(['price' => 10000], stock: 0))->create();
        CartItem::factory()->for($soldOut->cart)->for(onlyVariant(['price' => 5000]))->create();

        $summary = app(CartService::class)->summary($soldOut->cart, null);

        expect($summary->subtotal())->toBe(5000);
        expect($summary->hasUnavailableLines())->toBeTrue();
    });
});

describe('delivery fee', function () {
    it('charges the area fee below the free delivery amount', function () {
        app(Settings::class)->set(['delivery_free_over' => 30000]);
        $item = CartItem::factory()->for(onlyVariant(['price' => 10000]))->create(['quantity' => 2]);
        $zone = DeliveryZone::factory()->create(['fee' => 2500]);

        $summary = app(CartService::class)->summary($item->cart, $zone);

        expect($summary->deliveryFee())->toBe(2500);
        expect($summary->amountToFreeDelivery())->toBe(10000);
        expect($summary->total())->toBe(22500);
    });

    it('delivers free from the store-wide amount', function () {
        app(Settings::class)->set(['delivery_free_over' => 30000]);
        $item = CartItem::factory()->for(onlyVariant(['price' => 15000]))->create(['quantity' => 2]);

        $summary = app(CartService::class)->summary($item->cart, DeliveryZone::factory()->create(['fee' => 2500]));

        expect($summary->deliveryFee())->toBe(0);
        expect($summary->amountToFreeDelivery())->toBe(0);
    });

    it('uses an area’s own free delivery amount over the store-wide one', function () {
        app(Settings::class)->set(['delivery_free_over' => 30000]);
        $item = CartItem::factory()->for(onlyVariant(['price' => 15000]))->create(['quantity' => 2]);
        $farZone = DeliveryZone::factory()->create(['fee' => 4000, 'free_over' => 50000]);

        $summary = app(CartService::class)->summary($item->cart, $farZone);

        expect($summary->deliveryFee())->toBe(4000);
        expect($summary->amountToFreeDelivery())->toBe(20000);
    });

    it('waits for an area before charging delivery', function () {
        $item = CartItem::factory()->for(onlyVariant(['price' => 15000]))->create();

        $summary = app(CartService::class)->summary($item->cart, null);

        expect($summary->deliveryFee())->toBeNull();
        expect($summary->amountToFreeDelivery())->toBeNull();
        expect($summary->total())->toBe(15000);
    });
});

it('prunes bags untouched for 30 days', function () {
    $this->travelTo('2026-11-09 12:00:00');
    $stale = Cart::factory()->create(['updated_at' => now()->subDays(31)]);
    $recent = Cart::factory()->create(['updated_at' => now()->subDays(29)]);

    $this->artisan('model:prune', ['--model' => [Cart::class]])->assertSuccessful();

    $this->assertModelMissing($stale);
    $this->assertModelExists($recent);
});
