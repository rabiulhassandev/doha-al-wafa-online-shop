<?php

use App\Actions\CreateProductVariant;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;

it('builds the SKU from the product code, color and size', function () {
    $productColor = ProductColor::factory()
        ->for(Product::factory()->create(['code' => 'DW0012']))
        ->for(Color::factory()->create(['slug' => 'royal-blue']))
        ->create();
    $size = Size::factory()->letter('XL')->create();

    $variant = app(CreateProductVariant::class)->handle($productColor, $size);

    expect($variant->sku)->toBe('DW0012-ROYAL-BLUE-XL');
    expect($variant->stock)->toBe(0);
    expect($variant->is_active)->toBeTrue();
});

it('adds a number when the SKU is already taken', function () {
    ProductVariant::factory()->create(['sku' => 'DW0012-NAVY-56']);
    $productColor = ProductColor::factory()
        ->for(Product::factory()->create(['code' => 'DW0012']))
        ->for(Color::factory()->create(['slug' => 'navy']))
        ->create();

    $variant = app(CreateProductVariant::class)->handle($productColor, Size::factory()->create(['slug' => '56']));

    expect($variant->sku)->toBe('DW0012-NAVY-56-2');
});
