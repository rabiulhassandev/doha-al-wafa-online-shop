<?php

use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Size;
use App\Models\SizeGuide;
use App\Models\User;

it('renders every catalog create form', function (string $routeName, string $heading) {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route($routeName))
        ->assertOk()
        ->assertSee($heading);
})->with([
    'category' => ['admin.categories.create', 'Add category'],
    'collection' => ['admin.collections.create', 'Add collection'],
    'color' => ['admin.colors.create', 'Add color'],
    'size' => ['admin.sizes.create', 'Add size'],
    'size guide' => ['admin.size-guides.create', 'Add size guide'],
]);

it('renders every catalog edit form with the saved values', function (string $routeName, Closure $makeRecord, string $savedValue) {
    $record = $makeRecord();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route($routeName, $record))
        ->assertOk()
        ->assertSee($savedValue);
})->with([
    'category' => ['admin.categories.edit', fn () => Category::factory()->create(['name_ar' => 'نساء']), 'نساء'],
    'collection' => ['admin.collections.edit', fn () => Collection::factory()->scheduled()->create(['name_ar' => 'مجموعة العيد']), 'مجموعة العيد'],
    'color' => ['admin.colors.edit', fn () => Color::factory()->create(['hex' => '#1f2a44']), '#1f2a44'],
    'size' => ['admin.sizes.edit', fn () => Size::factory()->create(['name_en' => '56', 'slug' => '56']), 'Edit size 56'],
    'size guide' => ['admin.size-guides.edit', fn () => SizeGuide::factory()->create(['name' => 'Kaftan guide']), 'Kaftan guide'],
]);

it('shows the catalog menu to staff', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.categories.index'))
        ->assertSee(route('admin.size-guides.index'));
});
