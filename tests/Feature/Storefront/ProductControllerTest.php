<?php

use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\ProductImage;
use App\Models\SizeGuide;
use App\Models\User;
use App\Services\Settings;

it('opens the product in the color from the link', function () {
    $product = listedProduct(stockByColor: ['navy' => ['56' => 2], 'olive' => ['56' => 2]]);

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product, 'color' => 'olive']))
        ->assertOk()
        ->assertSee('<h1 class="mt-1 font-display text-3xl leading-tight text-balance">'.e($product->name_en).'</h1>', false)
        ->assertViewHas('pageData', fn (array $data) => $data['color'] === 'olive');
});

it('falls back to the color shown first for an unknown color', function () {
    $product = listedProduct(stockByColor: ['navy' => ['56' => 2], 'olive' => ['56' => 2]]);

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product, 'color' => 'purple']))
        ->assertOk()
        ->assertViewHas('pageData', fn (array $data) => $data['color'] === 'navy');
});

it('preselects a size from the link only when that color is sold in it', function (string $color, ?string $expectedSize) {
    $product = listedProduct(stockByColor: ['navy' => ['56' => 2], 'olive' => ['58' => 2]]);

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product, 'color' => $color, 'size' => '56']))
        ->assertOk()
        ->assertViewHas('pageData', fn (array $data) => $data['size'] === $expectedSize);
})->with([
    'sold in 56' => ['navy', '56'],
    'not sold in 56' => ['olive', null],
]);

it('tells the picker which sizes are sold out and shares stock only when it is low', function () {
    app(Settings::class)->set(['low_stock_threshold' => 3]);
    $product = listedProduct(stockByColor: ['navy' => ['54' => 0, '56' => 2, '58' => 9]]);

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product]))
        ->assertOk()
        ->assertViewHas('pageData', function (array $data): bool {
            $sizes = $data['colors']['navy']['sizes'];

            return $sizes['54']['available'] === false
                && $sizes['56']['lowStock'] === 2
                && $sizes['58']['available'] === true
                && $sizes['58']['lowStock'] === null;
        });
});

it('shows each size’s own price in the shopper’s language', function () {
    $product = listedProduct(['price' => 18500, 'compare_at_price' => 22000], ['navy' => ['56' => 1, '58' => 1]]);
    $product->variants()->whereHas('size', fn ($query) => $query->where('slug', '58'))->update(['price' => 19900]);

    $this->get(route('products.show', ['locale' => 'ar', 'product' => $product]))
        ->assertOk()
        ->assertSee('ابتداءً من 185 ر.ق')
        ->assertViewHas('pageData', fn (array $data) => $data['colors']['navy']['sizes']['56']['price'] === '185 ر.ق'
            && $data['colors']['navy']['sizes']['56']['compareAt'] === '220 ر.ق'
            && $data['colors']['navy']['sizes']['58']['price'] === '199 ر.ق');
});

it('returns 404 for products shoppers cannot see', function (Closure $makeProduct) {
    $product = $makeProduct();

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product]))->assertNotFound();
})->with([
    'draft' => fn () => listedProduct(['status' => 'draft']),
    'archived' => fn () => listedProduct(['status' => 'archived']),
    'no photos' => fn () => tap(listedProduct(), fn ($product) => $product->colors()->update(['is_active' => false])),
]);

it('lets team members preview a draft product', function () {
    $product = listedProduct(['status' => 'draft']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('products.show', ['locale' => 'en', 'product' => $product]))
        ->assertOk()
        ->assertSee('Preview: shoppers cannot see this product until it is set to Active.')
        ->assertSee('<meta name="robots" content="noindex">', false);
});

it('suggests other products from the same category', function () {
    $category = Category::factory()->create();
    $product = listedProduct(['category_id' => $category->id]);
    listedProduct(['name_en' => 'Matching kaftan', 'category_id' => $category->id]);
    listedProduct(['name_en' => 'Unrelated jelabiya']);

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product]))
        ->assertOk()
        ->assertSee('Matching kaftan')
        ->assertDontSee('Unrelated jelabiya');
});

it('shows the size guide in centimeters and inches', function () {
    $sizeGuide = SizeGuide::factory()->create([
        'columns' => [['en' => 'Length', 'ar' => 'الطول']],
        'rows' => [['size' => '56', 'values' => [142.24]]],
    ]);
    $product = listedProduct(['size_guide_id' => $sizeGuide->id]);

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product]))
        ->assertOk()
        ->assertSee('Size guide')
        ->assertSee('142.2')
        ->assertSee('56');
});

it('links the photo and canonical address for sharing', function () {
    $product = listedProduct();

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product, 'color' => 'navy']))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('products.show', ['locale' => 'en', 'product' => $product]).'">', false)
        ->assertSee('<meta property="og:image" content="'.$product->colors()->first()->images()->first()->url(1200).'">', false);
});

it('shows photo controls only for galleries with multiple images', function () {
    $product = listedProduct(stockByColor: ['navy' => ['56' => 2], 'olive' => ['56' => 2]]);
    $navy = $product->colors()->firstWhere('is_default', true);
    $secondPhoto = ProductImage::factory()->for($navy)->create(['sort_order' => 100]);

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product]))
        ->assertOk()
        ->assertSee('aria-label="Previous photo"', false)
        ->assertSee('aria-label="Show photo 2"', false)
        ->assertSee('aria-label="Next photo"', false)
        ->assertSee($secondPhoto->url(400))
        ->assertViewHas('pageData', fn (array $data) => count($data['colors']['navy']['images']) === 2
            && count($data['colors']['olive']['images']) === 1);

    $single = listedProduct();
    $this->get(route('products.show', ['locale' => 'ar', 'product' => $single]))
        ->assertOk()
        ->assertDontSee('data-photo-thumbnails', false)
        ->assertSee('تكبير الصورة 1');
});

it('gives the size picker what it needs to add to the bag', function () {
    app(Settings::class)->set(['max_quantity_per_item' => 4]);
    $product = listedProduct(stockByColor: ['navy' => ['56' => 2]]);

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product]))
        ->assertOk()
        ->assertViewHas('pageData', fn (array $data) => $data['maxQuantity'] === 4
            && $data['colors']['navy']['sizes']['56']['variantId'] === $product->variants()->sole()->id);
});

it('shows delivery dates that skip Fridays', function () {
    $this->travelTo('2026-11-11 10:00:00');
    DeliveryZone::factory()->create(['eta_min_days' => 1, 'eta_max_days' => 3]);
    $product = listedProduct();

    $this->get(route('products.show', ['locale' => 'en', 'product' => $product]))
        ->assertOk()
        ->assertSee('Delivered across Qatar between Thu 12 Nov and Sun 15 Nov');
});
