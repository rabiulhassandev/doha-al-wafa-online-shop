<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\CatalogDefaultsSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoProductsSeeder;
use Illuminate\Support\Facades\Storage;

it('creates ready-to-sell demo products with photos and stock', function () {
    Storage::fake('public');
    $this->seed(DatabaseSeeder::class);

    expect(Product::query()->where('status', ProductStatus::Active)->count())->toBe(4);
    expect(ProductVariant::query()->count())->toBe(13);
    expect(ProductColor::query()->whereNull('color_id')->count())->toBe(4);
    $this->assertDatabaseCount('colors', 0);
    $this->assertDatabaseCount('product_images', 8);
    Storage::disk('public')->assertExists(ProductImage::query()->first()->directory.'/800.webp');

    $product = Product::query()->with(['colors.color', 'colors.images', 'variants'])->first();
    expect($product->setupIssues())->toBe([]);
    expect($product->published_at)->not->toBeNull();

    $this->get(route('search', ['locale' => 'en', 'q' => 'Classic crepe']))
        ->assertOk()
        ->assertViewHas('products', fn ($products) => $products->total() === 1);
});

it('does not duplicate products when run again', function () {
    Storage::fake('public');
    $this->seed(CatalogDefaultsSeeder::class);
    $this->seed(DemoProductsSeeder::class);

    $this->seed(DemoProductsSeeder::class);

    $this->assertDatabaseCount('products', 4);
});
