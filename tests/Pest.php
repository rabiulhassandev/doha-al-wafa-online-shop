<?php

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a product shoppers can see, with a photo for each color and stock for each size.
 *
 * Colors and sizes are matched by slug, so products in one test share them.
 *
 * @param  array<string, mixed>  $attributes
 * @param  array<string, array<string|int, int>>  $stockByColor  e.g. ['navy' => ['56' => 3, '58' => 0]]
 */
function listedProduct(array $attributes = [], array $stockByColor = ['navy' => ['56' => 3]]): Product
{
    $product = Product::factory()->active()->create($attributes);
    $isFirstColor = true;

    foreach ($stockByColor as $colorSlug => $stockBySize) {
        $color = Color::query()->firstWhere('slug', $colorSlug)
            ?? Color::factory()->create(['slug' => $colorSlug, 'name_en' => Str::title($colorSlug)]);
        $productColor = ProductColor::factory()->for($product)->for($color)->create(['is_default' => $isFirstColor]);
        ProductImage::factory()->for($productColor)->create();

        foreach ($stockBySize as $sizeSlug => $stock) {
            $size = Size::query()->firstWhere('slug', (string) $sizeSlug)
                ?? Size::factory()->create(['slug' => (string) $sizeSlug, 'name_en' => (string) $sizeSlug]);
            ProductVariant::factory()->for($productColor)->for($product)->for($size)->inStock($stock)->create();
        }

        $isFirstColor = false;
    }

    return $product;
}
