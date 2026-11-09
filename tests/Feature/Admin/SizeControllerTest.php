<?php

use App\Enums\SizeGroup;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;

/**
 * Build a complete size form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function sizePayload(array $overrides = []): array
{
    return [
        'name_en' => '56',
        'name_ar' => '',
        'slug' => '',
        'group' => 'length',
        'sort_order' => '30',
        ...$overrides,
    ];
}

describe('index', function () {
    it('lists sizes from smallest to largest', function () {
        Size::factory()->create(['name_en' => '58', 'slug' => '58', 'sort_order' => 2]);
        Size::factory()->create(['name_en' => '54', 'slug' => '54', 'sort_order' => 1]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.sizes.index'))
            ->assertOk()
            ->assertSeeInOrder(['54', '58']);
    });
});

describe('store', function () {
    it('adds a size', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.sizes.store'), sizePayload())
            ->assertRedirectToRoute('admin.sizes.index')
            ->assertSessionHas('status', 'Size “56” was added.');

        $size = Size::query()->sole();
        expect($size->slug)->toBe('56');
        expect($size->group)->toBe(SizeGroup::Length);
        expect($size->name_ar)->toBeNull();
        expect($size->sort_order)->toBe(30);
    });

    it('keeps an Arabic name for word sizes', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.sizes.store'), sizePayload(['name_en' => 'Free size', 'name_ar' => 'مقاس واحد', 'group' => 'one_size']))
            ->assertRedirectToRoute('admin.sizes.index');

        $this->assertDatabaseHas('sizes', ['slug' => 'free-size', 'name_ar' => 'مقاس واحد', 'group' => 'one_size']);
    });

    it('rejects an unknown size type', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.sizes.store'), sizePayload(['group' => 'shoe']))
            ->assertSessionHasErrors(['group' => 'The selected size type is invalid.']);
    });

    it('rejects a URL name that is already used', function () {
        Size::factory()->create(['slug' => 'm']);

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.sizes.store'), sizePayload(['name_en' => 'M', 'slug' => 'm', 'group' => 'letter']))
            ->assertSessionHasErrors(['slug' => 'The URL name has already been taken.']);
    });
});

describe('update', function () {
    it('updates a size', function () {
        $size = Size::factory()->create(['slug' => '56']);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.sizes.update', $size), sizePayload(['slug' => '56', 'sort_order' => '35']))
            ->assertRedirectToRoute('admin.sizes.index');

        expect($size->refresh()->sort_order)->toBe(35);
    });
});

describe('destroy', function () {
    it('deletes a size', function () {
        $size = Size::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.sizes.destroy', $size))
            ->assertRedirectToRoute('admin.sizes.index');

        $this->assertModelMissing($size);
    });

    it('refuses to delete a size that products use', function () {
        $size = Size::factory()->create(['name_en' => '56', 'slug' => '56']);
        ProductVariant::factory()->for($size)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->from(route('admin.sizes.index'))
            ->delete(route('admin.sizes.destroy', $size))
            ->assertSessionHas('error', 'Size “56” is used by products. Switch it off in their stock grids instead.');

        $this->assertModelExists($size);
    });
});
