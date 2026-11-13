<?php

use App\Models\Category;
use App\Models\Collection;

it('finds Arabic names whatever spelling the shopper uses', function (string $query) {
    listedProduct(['name_en' => 'Embroidered kaftan', 'name_ar' => 'جلابيّة مطرّزة بالأحمر']);

    $this->get(route('search', ['locale' => 'ar', 'q' => $query]))
        ->assertOk()
        ->assertViewHas('products', fn ($products) => $products->total() === 1);
})->with([
    'taa marbuta as haa' => ['جلابيه'],
    'without hamza' => ['بالاحمر'],
    'exact spelling' => ['مطرّزة'],
]);

it('finds products by English name, code or color', function (string $query) {
    $product = listedProduct(['name_en' => 'Embroidered kaftan', 'code' => 'KFT01'], ['navy' => ['56' => 1]]);
    listedProduct(['name_en' => 'Plain jelabiya', 'code' => 'PLN01'], ['olive' => ['56' => 1]]);

    $this->get(route('search', ['locale' => 'en', 'q' => $query]))
        ->assertOk()
        ->assertSee($product->name_en)
        ->assertDontSee('Plain jelabiya');
})->with([
    'name' => ['EMBROIDERED'],
    'code' => ['kft01'],
    'color name' => ['navy'],
]);

it('treats percent signs as plain text', function () {
    listedProduct(['name_en' => 'Plain jelabiya']);

    $this->get(route('search', ['locale' => 'en', 'q' => '%']))
        ->assertOk()
        ->assertViewHas('products', fn ($products) => $products->total() === 0);
});

it('finds a product by parent category, collection, and SKU even when it has no color', function (string $term) {
    $parent = Category::factory()->create(['name_en' => 'Women', 'name_ar' => 'أزياء نسائية']);
    $category = Category::factory()->for($parent, 'parent')->create(['name_en' => 'Evening wear']);
    $collection = Collection::factory()->create(['name_en' => 'Eid Edit']);
    $product = listedProduct(['name_en' => 'Embroidered kaftan', 'category_id' => $category->id]);
    $product->colors()->update(['color_id' => null]);
    $product->variants()->update(['sku' => 'JLB-56']);
    $product->collections()->attach($collection);
    listedProduct(['name_en' => 'Plain tunic']);

    $this->get(route('search', ['locale' => 'en', 'q' => $term]))
        ->assertOk()
        ->assertViewHas('products', fn ($results) => $results->total() === 1 && $results->first()->is($product));
})->with(['Women embroidered', 'ازياء', 'Evening wear', 'Eid Edit', 'jlb-56']);

it('does not match an unpublished collection or a disabled variant', function (string $term) {
    $product = listedProduct(stockByColor: ['navy' => ['56' => 2, '58' => 2]]);
    $collection = Collection::factory()->create(['name_en' => 'Future promotion', 'starts_at' => now()->addDay()]);
    $product->collections()->attach($collection);
    $product->variants()->whereHas('size', fn ($size) => $size->where('slug', '58'))
        ->update(['is_active' => false, 'sku' => 'HIDDEN-58']);

    $this->get(route('search', ['locale' => 'en', 'q' => $term]))
        ->assertOk()
        ->assertViewHas('products', fn ($results) => $results->total() === 0);
})->with(['Future promotion', 'HIDDEN-58']);

it('escapes the search term on the page', function () {
    $this->get(route('search', ['locale' => 'en', 'q' => '<b>bold</b>']))
        ->assertOk()
        ->assertSee('&lt;b&gt;bold&lt;/b&gt;', false)
        ->assertDontSee('<b>bold</b>', false);
});
