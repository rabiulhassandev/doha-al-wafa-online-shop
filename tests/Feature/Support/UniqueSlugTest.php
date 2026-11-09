<?php

use App\Models\Category;
use App\Support\UniqueSlug;

it('builds a slug from the text', function () {
    expect(UniqueSlug::generate(Category::class, 'Eid Specials 2026'))->toBe('eid-specials-2026');
});

it('adds the next free number when the slug is taken', function () {
    Category::factory()->create(['slug' => 'women']);
    Category::factory()->create(['slug' => 'women-2']);

    expect(UniqueSlug::generate(Category::class, 'Women'))->toBe('women-3');
});

it('ignores the record being edited', function () {
    $category = Category::factory()->create(['slug' => 'women']);

    expect(UniqueSlug::generate(Category::class, 'Women', $category->id))->toBe('women');
});

it('falls back to a placeholder when the text has no usable characters', function () {
    expect(UniqueSlug::generate(Category::class, '!!!'))->toBe('item');
});
