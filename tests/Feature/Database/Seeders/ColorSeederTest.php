<?php

use App\Models\Color;
use Database\Seeders\ColorSeeder;

it('seeds common clothing colors with bilingual names and usable swatches', function () {
    $this->seed(ColorSeeder::class);

    $this->assertDatabaseHas('colors', ['slug' => 'black', 'name_en' => 'Black', 'name_ar' => 'أسود', 'hex' => '#000000']);
    $this->assertDatabaseHas('colors', ['slug' => 'navy', 'name_ar' => 'كحلي', 'hex' => '#000080']);
    $this->assertDatabaseHas('colors', ['slug' => 'rose-gold', 'name_ar' => 'ذهبي وردي']);
    expect(Color::query()->count())->toBeGreaterThanOrEqual(70);

    foreach (Color::query()->get() as $color) {
        expect($color->hex)->toMatch('/^#[0-9a-f]{6}$/');
        expect($color->name_ar)->not->toBeEmpty();
        expect($color->sort_order)->toBeGreaterThan(0);
    }
});

it('can run repeatedly while preserving existing palette edits and custom colors', function () {
    $navy = Color::factory()->create([
        'slug' => 'navy', 'name_en' => 'Custom Navy', 'name_ar' => 'كحلي خاص',
        'hex' => '#122334', 'sort_order' => 5,
    ]);
    $custom = Color::factory()->create(['slug' => 'custom-shade']);

    $this->seed(ColorSeeder::class);
    $count = Color::query()->count();
    $this->seed(ColorSeeder::class);

    expect(Color::query()->count())->toBe($count);
    expect($navy->fresh()->only(['name_en', 'name_ar', 'hex', 'sort_order']))->toBe([
        'name_en' => 'Custom Navy', 'name_ar' => 'كحلي خاص', 'hex' => '#122334', 'sort_order' => 5,
    ]);
    $this->assertModelExists($custom);
});
