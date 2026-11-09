<?php

use App\Models\Category;
use App\Models\Collection;

it('shows the store menu on every visit with the database cache store', function () {
    config(['cache.default' => 'database']);
    Category::factory()->create(['name_en' => 'Women']);
    Collection::factory()->create(['name_en' => 'Eid collection']);

    $this->get(route('home', ['locale' => 'en']))->assertOk()->assertSee('Women');

    $this->get(route('home', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Women')
        ->assertSee('Eid collection');
});

it('leaves hidden categories and collections out of the menu', function () {
    Category::factory()->hidden()->create(['name_en' => 'Secret category']);
    Collection::factory()->scheduled()->create(['name_en' => 'Coming collection']);

    $this->get(route('home', ['locale' => 'en']))
        ->assertOk()
        ->assertDontSee('Secret category')
        ->assertDontSee('Coming collection');
});
