<?php

use App\Models\Color;
use App\Models\ProductColor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Build a complete color form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function colorPayload(array $overrides = []): array
{
    return [
        'name_en' => 'Navy',
        'name_ar' => 'كحلي',
        'slug' => '',
        'hex' => '#1f2a44',
        'sort_order' => '0',
        ...$overrides,
    ];
}

describe('index', function () {
    it('lists the palette in display order', function () {
        Color::factory()->create(['name_en' => 'Navy', 'sort_order' => 2]);
        Color::factory()->create(['name_en' => 'Black', 'sort_order' => 1]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.colors.index'))
            ->assertOk()
            ->assertSeeInOrder(['Black', 'Navy']);
    });
});

describe('store', function () {
    it('adds a color with its hex code in lowercase', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.colors.store'), colorPayload(['hex' => '#1F2A44']))
            ->assertRedirectToRoute('admin.colors.index')
            ->assertSessionHas('status', '“Navy” was added to the palette.');

        $color = Color::query()->sole();
        expect($color->hex)->toBe('#1f2a44');
        expect($color->slug)->toBe('navy');
    });

    it('rejects a color code that is not a 6-digit hex code', function (string $hex) {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.colors.store'), colorPayload(['hex' => $hex]))
            ->assertSessionHasErrors(['hex' => 'Enter the color as a 6-digit hex code, for example #1f2a44.']);

        $this->assertDatabaseCount('colors', 0);
    })->with([
        'a color name' => ['navy'],
        'missing the hash' => ['1f2a44'],
        'three digits' => ['#123'],
        'a style injection' => ['#123456;background:url(x)'],
    ]);

    it('crops the fabric photo to a square swatch', function () {
        Storage::fake('public');

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.colors.store'), colorPayload(['swatch' => UploadedFile::fake()->image('print.jpg', 600, 400)]))
            ->assertRedirectToRoute('admin.colors.index');

        $swatchPath = Color::query()->sole()->swatch_path;
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($swatchPath));
        expect([$width, $height])->toBe([160, 160]);
    });
});

describe('update', function () {
    it('updates a color', function () {
        $color = Color::factory()->create(['slug' => 'navy']);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.colors.update', $color), colorPayload(['slug' => 'navy', 'name_en' => 'Midnight navy']))
            ->assertRedirectToRoute('admin.colors.index');

        expect($color->refresh()->name_en)->toBe('Midnight navy');
    });
});

describe('destroy', function () {
    it('removes a color from the palette', function () {
        $color = Color::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.colors.destroy', $color))
            ->assertRedirectToRoute('admin.colors.index');

        $this->assertModelMissing($color);
    });

    it('refuses to delete a color that products use', function () {
        $color = Color::factory()->create(['name_en' => 'Navy']);
        ProductColor::factory()->for($color)->count(2)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->from(route('admin.colors.index'))
            ->delete(route('admin.colors.destroy', $color))
            ->assertRedirectToRoute('admin.colors.index')
            ->assertSessionHas('error', '“Navy” is used by 2 products. Remove it from them first.');

        $this->assertModelExists($color);
    });
});
