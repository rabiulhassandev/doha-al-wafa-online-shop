<?php

use App\Models\Category;
use App\Models\Color;
use Database\Seeders\CatalogDefaultsSeeder;

it('seeds the starter catalog', function () {
    $this->seed(CatalogDefaultsSeeder::class);

    $this->assertDatabaseCount('categories', 3);
    $this->assertDatabaseCount('collections', 3);
    $this->assertDatabaseCount('colors', 18);
    $this->assertDatabaseCount('sizes', 13);
    $this->assertDatabaseCount('size_guides', 1);
    $this->assertDatabaseHas('colors', ['slug' => 'navy', 'name_ar' => 'كحلي', 'hex' => '#1f2a44']);
    $this->assertDatabaseHas('sizes', ['slug' => 'free-size', 'name_ar' => 'مقاس واحد', 'group' => 'one_size']);
});

it('runs again without duplicating rows', function () {
    $this->seed(CatalogDefaultsSeeder::class);

    $this->seed(CatalogDefaultsSeeder::class);

    $this->assertDatabaseCount('categories', 3);
    $this->assertDatabaseCount('colors', 18);
    $this->assertDatabaseCount('sizes', 13);
    $this->assertDatabaseCount('size_guides', 1);
});

it('keeps the owner\'s edits when it runs again', function () {
    $this->seed(CatalogDefaultsSeeder::class);
    Color::query()->where('slug', 'navy')->update(['hex' => '#000080']);
    Category::query()->where('slug', 'women')->update(['name_en' => 'Ladies']);

    $this->seed(CatalogDefaultsSeeder::class);

    expect(Color::query()->where('slug', 'navy')->value('hex'))->toBe('#000080');
    expect(Category::query()->where('slug', 'women')->value('name_en'))->toBe('Ladies');
});
