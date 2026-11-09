<?php

it('lists products shoppers can buy', function () {
    listedProduct(['name_en' => 'Classic crepe jelabiya']);
    listedProduct(['name_en' => 'Secret draft', 'status' => 'draft']);

    $this->get(route('shop', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Classic crepe jelabiya')
        ->assertDontSee('Secret draft');
});

it('shows Arabic names on the Arabic page', function () {
    listedProduct(['name_en' => 'Classic crepe jelabiya', 'name_ar' => 'جلابية كريب كلاسيكية']);

    $this->get(route('shop', ['locale' => 'ar']))
        ->assertOk()
        ->assertSee('جلابية كريب كلاسيكية')
        ->assertDontSee('Classic crepe jelabiya');
});

it('matches a color and size filter on the same color and size', function () {
    listedProduct(['name_en' => 'Navy in 56'], ['navy' => ['56' => 2]]);
    listedProduct(['name_en' => 'Navy in 54 olive in 56'], ['navy' => ['54' => 2], 'olive' => ['56' => 2]]);

    $this->get(route('shop', ['locale' => 'en', 'color' => 'navy', 'size' => '56']))
        ->assertOk()
        ->assertSee('Navy in 56')
        ->assertDontSee('Navy in 54 olive in 56');
});

it('treats several colors as either one', function () {
    listedProduct(['name_en' => 'Navy only'], ['navy' => ['56' => 1]]);
    listedProduct(['name_en' => 'Olive only'], ['olive' => ['56' => 1]]);
    listedProduct(['name_en' => 'Rose only'], ['rose' => ['56' => 1]]);

    $this->get(route('shop', ['locale' => 'en', 'color' => 'navy,olive']))
        ->assertOk()
        ->assertSee('Navy only')
        ->assertSee('Olive only')
        ->assertDontSee('Rose only');
});

it('hides sold out products when asked for stock only', function () {
    listedProduct(['name_en' => 'Plenty left'], ['navy' => ['56' => 4]]);
    listedProduct(['name_en' => 'All gone'], ['navy' => ['56' => 0]]);

    $this->get(route('shop', ['locale' => 'en', 'in_stock' => '1']))
        ->assertOk()
        ->assertSee('Plenty left')
        ->assertDontSee('All gone');
});

it('filters by price in riyals', function () {
    listedProduct(['name_en' => 'Budget jelabiya', 'price' => 9500]);
    listedProduct(['name_en' => 'Premium kaftan', 'price' => 42000]);

    $this->get(route('shop', ['locale' => 'en', 'min' => '100', 'max' => '500']))
        ->assertOk()
        ->assertSee('Premium kaftan')
        ->assertDontSee('Budget jelabiya');
});

it('sorts by price with sold out products last', function () {
    listedProduct(['name_en' => 'Cheap sold out', 'price' => 5000], ['navy' => ['56' => 0]]);
    listedProduct(['name_en' => 'Mid price', 'price' => 20000]);
    listedProduct(['name_en' => 'Low price', 'price' => 10000]);

    $this->get(route('shop', ['locale' => 'en', 'sort' => 'price_asc']))
        ->assertOk()
        ->assertSeeInOrder(['Low price', 'Mid price', 'Cheap sold out']);
});

it('offers only the colors and sizes of listed products as filters', function () {
    listedProduct(stockByColor: ['navy' => ['56' => 1]]);
    listedProduct(['status' => 'draft'], ['olive' => ['58' => 1]]);

    $this->get(route('shop', ['locale' => 'en']))
        ->assertOk()
        ->assertViewHas('facets', fn (array $facets) => $facets['colors']->pluck('slug')->all() === ['navy']
            && $facets['sizes']->pluck('slug')->all() === ['56']);
});

it('pages through products with a load more link', function () {
    foreach (range(1, 25) as $number) {
        listedProduct(['name_en' => "Jelabiya {$number}"]);
    }

    $this->get(route('shop', ['locale' => 'en', 'color' => 'navy']))
        ->assertOk()
        ->assertSee('data-load-more', false)
        ->assertSee('page=2', false)
        ->assertViewHas('products', fn ($products) => $products->count() === 24 && $products->total() === 25);
});

it('shows the photo of the filtered color on product cards', function () {
    $product = listedProduct(stockByColor: ['navy' => ['56' => 1], 'olive' => ['56' => 1]]);
    $oliveImage = $product->colors()->whereHas('color', fn ($query) => $query->where('slug', 'olive'))->first()->images()->first();

    $this->get(route('shop', ['locale' => 'en', 'color' => 'olive']))
        ->assertOk()
        ->assertSee($oliveImage->url(800), false);
});

it('ignores invalid filter values without echoing them', function () {
    listedProduct(['name_en' => 'Classic crepe jelabiya']);

    $this->get(route('shop', ['locale' => 'en', 'color' => "<script>alert('x')</script>", 'sort' => 'drop table']))
        ->assertOk()
        ->assertSee('Classic crepe jelabiya')
        ->assertDontSee("<script>alert('x')</script>", false);
});
