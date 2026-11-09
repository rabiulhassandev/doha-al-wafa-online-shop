<?php

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

it('escapes the search term on the page', function () {
    $this->get(route('search', ['locale' => 'en', 'q' => '<b>bold</b>']))
        ->assertOk()
        ->assertSee('&lt;b&gt;bold&lt;/b&gt;', false)
        ->assertDontSee('<b>bold</b>', false);
});
