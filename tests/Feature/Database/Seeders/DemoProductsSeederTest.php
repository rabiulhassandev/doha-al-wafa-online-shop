<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\CatalogDefaultsSeeder;
use Database\Seeders\DemoProductsSeeder;
use Illuminate\Support\Facades\Storage;

it('creates ready-to-sell demo products with photos and stock', function () {
    Storage::fake('public');
    $this->seed(CatalogDefaultsSeeder::class);

    $this->seed(DemoProductsSeeder::class);

    expect(Product::query()->where('status', ProductStatus::Active)->count())->toBe(4);
    expect(ProductVariant::query()->count())->toBe(3 * 4 + 3 * 4 + 3 * 4 + 2 * 1);
    $this->assertDatabaseCount('product_images', 22);
    Storage::disk('public')->assertExists(ProductImage::query()->first()->directory.'/800.webp');

    $product = Product::query()->with(['colors.color', 'colors.images', 'variants'])->first();
    expect($product->setupIssues())->toBe([]);
    expect($product->published_at)->not->toBeNull();
});

it('does not duplicate products when run again', function () {
    Storage::fake('public');
    $this->seed(CatalogDefaultsSeeder::class);
    $this->seed(DemoProductsSeeder::class);

    $this->seed(DemoProductsSeeder::class);

    $this->assertDatabaseCount('products', 4);
});
