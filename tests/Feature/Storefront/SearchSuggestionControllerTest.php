<?php

use App\Models\Category;

it('suggests products and categories as JSON', function () {
    $product = listedProduct(['name_en' => 'Navy kaftan', 'name_ar' => 'قفطان كحلي', 'price' => 18500]);
    Category::factory()->create(['name_en' => 'Kaftans', 'slug' => 'kaftans']);

    $this->getJson(route('search.suggestions', ['locale' => 'en', 'q' => 'kaftan']))
        ->assertOk()
        ->assertJsonPath('products.0.name', 'Navy kaftan')
        ->assertJsonPath('products.0.price', 'QAR 185')
        ->assertJsonPath('products.0.url', route('products.show', ['locale' => 'en', 'product' => $product, 'color' => 'navy']))
        ->assertJsonPath('categories.0.name', 'Kaftans');
});

it('uses the shopper’s language', function () {
    listedProduct(['name_en' => 'Navy kaftan', 'name_ar' => 'قفطان كحلي', 'price' => 18500]);

    $this->getJson(route('search.suggestions', ['locale' => 'ar', 'q' => 'قفطان']))
        ->assertOk()
        ->assertJsonPath('products.0.name', 'قفطان كحلي')
        ->assertJsonPath('products.0.price', '185 ر.ق');
});

it('waits for at least two characters', function () {
    listedProduct(['name_en' => 'Navy kaftan']);

    $this->getJson(route('search.suggestions', ['locale' => 'en', 'q' => 'n']))
        ->assertOk()
        ->assertExactJson(['products' => [], 'categories' => []]);
});
