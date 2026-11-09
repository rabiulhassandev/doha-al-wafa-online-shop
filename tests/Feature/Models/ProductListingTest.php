<?php

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;

it('lists active products that have a photographed color in an offered size', function () {
    $product = listedProduct();

    expect(Product::query()->listed()->pluck('id')->all())->toBe([$product->id]);
});

it('hides products that shoppers cannot buy', function (Closure $makeProduct) {
    $makeProduct();

    $this->assertDatabaseCount('products', 1);
    expect(Product::query()->listed()->count())->toBe(0);
})->with([
    'draft' => fn () => listedProduct(['status' => 'draft']),
    'archived' => fn () => listedProduct(['status' => 'archived']),
    'no colors' => fn () => Product::factory()->active()->create(),
    'color without photos' => fn () => ProductVariant::factory()->for(ProductColor::factory()->for($product = Product::factory()->active()->create()))->for($product)->create(),
    'hidden color' => fn () => tap(listedProduct(), fn (Product $product) => $product->colors()->update(['is_active' => false])),
    'no sizes offered' => fn () => tap(listedProduct(), fn (Product $product) => $product->variants()->update(['is_active' => false])),
]);

it('keeps sold out products listed', function () {
    listedProduct(stockByColor: ['navy' => ['56' => 0]]);

    expect(Product::query()->listed()->count())->toBe(1);
});

it('only offers colors that have photos', function () {
    $product = listedProduct();
    $unphotographed = ProductColor::factory()->for($product)->create();
    ProductVariant::factory()->for($unphotographed)->for($product)->create();
    $product->load(['colors.color', 'colors.images', 'colors.variants']);

    expect($product->listedColors())->toHaveCount(1);
});

it('shows a filtered color first, then the default color', function () {
    $product = listedProduct(stockByColor: ['navy' => ['56' => 1], 'olive' => ['56' => 1]]);
    $product->load(['colors.color', 'colors.images', 'colors.variants']);
    $olive = $product->colors->firstWhere('color.slug', 'olive');

    expect($product->displayColor()->color->slug)->toBe('navy');
    expect($product->displayColor([$olive->color_id])->color->slug)->toBe('olive');
});

it('reports the price range across sizes with their own price', function () {
    $product = listedProduct(['price' => 18500], ['navy' => ['56' => 1, '58' => 1]]);
    $product->variants()->whereHas('size', fn ($query) => $query->where('slug', '58'))->update(['price' => 19900]);
    $product->load(['colors.color', 'colors.images', 'colors.variants']);

    expect($product->priceRange())->toBe([18500, 19900]);
});
