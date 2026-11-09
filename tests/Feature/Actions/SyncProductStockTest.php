<?php

use App\Actions\AdjustStock;
use App\Actions\SyncProductStock;
use App\Enums\StockMovementReason;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;

it('creates every color and size with its stock', function () {
    $product = Product::factory()->create(['code' => 'DW0012']);
    $navy = ProductColor::factory()->for($product)->for(Color::factory()->create(['slug' => 'navy']))->create();
    $olive = ProductColor::factory()->for($product)->for(Color::factory()->create(['slug' => 'olive']))->create();
    $size54 = Size::factory()->create(['name_en' => '54', 'slug' => '54']);
    $size56 = Size::factory()->create(['name_en' => '56', 'slug' => '56']);

    app(SyncProductStock::class)->handle($product, [$size54->id, $size56->id], [
        $navy->id => [
            $size54->id => ['stock' => 3, 'original_stock' => 0, 'offered' => true],
            $size56->id => ['stock' => 0, 'original_stock' => 0, 'offered' => true],
        ],
        $olive->id => [
            $size54->id => ['stock' => 5, 'original_stock' => 0, 'offered' => true],
            $size56->id => ['stock' => 2, 'original_stock' => 0, 'offered' => false],
        ],
    ], User::factory()->staff()->create());

    $variants = ProductVariant::query()->orderBy('sku')->get(['sku', 'stock', 'is_active'])->toArray();
    expect($variants)->toBe([
        ['sku' => 'DW0012-NAVY-54', 'stock' => 3, 'is_active' => true],
        ['sku' => 'DW0012-NAVY-56', 'stock' => 0, 'is_active' => true],
        ['sku' => 'DW0012-OLIVE-54', 'stock' => 5, 'is_active' => true],
        ['sku' => 'DW0012-OLIVE-56', 'stock' => 2, 'is_active' => false],
    ]);
    $this->assertDatabaseCount('stock_movements', 3);
    $this->assertDatabaseHas('stock_movements', ['sku' => 'DW0012-OLIVE-54', 'change' => 5, 'reason' => StockMovementReason::StockGrid->value]);
});

it('keeps a sale made while the grid was open', function () {
    $variant = ProductVariant::factory()->inStock(5)->create();
    $product = $variant->product;
    app(AdjustStock::class)->change($variant, -2, StockMovementReason::Damaged);

    app(SyncProductStock::class)->handle($product, [$variant->size_id], [
        $variant->product_color_id => [
            $variant->size_id => ['stock' => 6, 'original_stock' => 5, 'offered' => true],
        ],
    ], User::factory()->staff()->create());

    expect($variant->refresh()->stock)->toBe(4);
});

it('stops offering sizes that were switched off but keeps their stock', function () {
    $variant = ProductVariant::factory()->inStock(4)->create();
    $otherSize = Size::factory()->create();

    app(SyncProductStock::class)->handle($variant->product, [$otherSize->id], [], User::factory()->staff()->create());

    $variant->refresh();
    expect($variant->is_active)->toBeFalse();
    expect($variant->stock)->toBe(4);
});

it('offers a size again with its old stock when it is switched back on', function () {
    $variant = ProductVariant::factory()->notOffered()->inStock(4)->create();

    app(SyncProductStock::class)->handle($variant->product, [$variant->size_id], [
        $variant->product_color_id => [
            $variant->size_id => ['stock' => 4, 'original_stock' => 4, 'offered' => true],
        ],
    ], User::factory()->staff()->create());

    $variant->refresh();
    expect($variant->is_active)->toBeTrue();
    expect($variant->stock)->toBe(4);
    $this->assertDatabaseCount('stock_movements', 0);
});
