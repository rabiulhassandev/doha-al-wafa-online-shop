<?php

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductVariant;

it('needs a color before anything else', function () {
    $product = Product::factory()->create()->load(['colors.color', 'colors.images', 'variants']);

    expect($product->setupIssues())->toBe(['Add at least one color.']);
});

it('lists colors without photos and missing sizes', function () {
    $product = Product::factory()->create();
    ProductColor::factory()->for($product)->for(Color::factory()->create(['name_en' => 'Navy']))->create();
    $olive = ProductColor::factory()->for($product)->create();
    ProductImage::factory()->for($olive)->create();
    ProductColor::factory()->for($product)->hidden()->for(Color::factory()->create(['name_en' => 'Rose']))->create();

    $product->load(['colors.color', 'colors.images', 'variants']);

    expect($product->setupIssues())->toBe(['Add photos for Navy.', 'Choose sizes and enter stock.']);
});

it('is ready once every shown color has photos and a size is offered', function () {
    $product = Product::factory()->create();
    $navy = ProductColor::factory()->for($product)->create();
    ProductImage::factory()->for($navy)->create();
    ProductVariant::factory()->for($navy)->for($product)->create();

    $product->load(['colors.color', 'colors.images', 'variants']);

    expect($product->setupIssues())->toBe([]);
});

it('uses the custom color label when one is set', function () {
    $productColor = ProductColor::factory()
        ->for(Color::factory()->create(['name_en' => 'Navy']))
        ->create(['label_en' => 'Navy with gold embroidery']);

    expect($productColor->nameEn())->toBe('Navy with gold embroidery');
});
