<?php

use App\Models\Category;

it('lists products from the category and its sub-categories', function () {
    $women = Category::factory()->create(['slug' => 'women', 'name_en' => 'Women']);
    $kaftans = Category::factory()->childOf($women)->create(['name_en' => 'Kaftans']);
    listedProduct(['name_en' => 'Women jelabiya', 'category_id' => $women->id]);
    listedProduct(['name_en' => 'Embroidered kaftan', 'category_id' => $kaftans->id]);
    listedProduct(['name_en' => 'Men jelabiya']);

    $this->get(route('category.show', ['locale' => 'en', 'category' => 'women']))
        ->assertOk()
        ->assertSee('Women jelabiya')
        ->assertSee('Embroidered kaftan')
        ->assertDontSee('Men jelabiya');
});

it('shows the category name in the shopper’s language', function () {
    $women = Category::factory()->create(['slug' => 'women', 'name_en' => 'Women', 'name_ar' => 'نساء']);

    $this->get(route('category.show', ['locale' => 'ar', 'category' => $women]))
        ->assertOk()
        ->assertSee('<h1 class="font-display text-3xl sm:text-4xl">نساء</h1>', false);
});

it('returns 404 for hidden categories', function (Closure $makeCategory) {
    $category = $makeCategory();

    $this->get(route('category.show', ['locale' => 'en', 'category' => $category]))->assertNotFound();
})->with([
    'hidden category' => fn () => Category::factory()->hidden()->create(),
    'under a hidden parent' => fn () => Category::factory()->childOf(Category::factory()->hidden()->create())->create(),
]);

it('returns 404 for a category that does not exist', function () {
    $this->get('/en/category/nothing-here')->assertNotFound();
});
