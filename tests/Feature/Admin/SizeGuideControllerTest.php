<?php

use App\Models\SizeGuide;
use App\Models\User;

/**
 * Build a complete size guide form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function sizeGuidePayload(array $overrides = []): array
{
    return [
        'name' => 'Jelabiya by length',
        'columns' => [
            ['en' => 'Length', 'ar' => 'الطول'],
            ['en' => 'Chest', 'ar' => 'الصدر'],
        ],
        'rows' => [
            ['size' => '54', 'values' => ['137', '112']],
            ['size' => '56', 'values' => ['142.5', '']],
        ],
        'notes_en' => 'Measured flat.',
        'notes_ar' => '',
        ...$overrides,
    ];
}

describe('store', function () {
    it('saves the table with numbers in centimeters and empty cells as blanks', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.size-guides.store'), sizeGuidePayload())
            ->assertRedirectToRoute('admin.size-guides.index')
            ->assertSessionHas('status', '“Jelabiya by length” was created.');

        $sizeGuide = SizeGuide::query()->sole();
        expect($sizeGuide->columns)->toBe([
            ['en' => 'Length', 'ar' => 'الطول'],
            ['en' => 'Chest', 'ar' => 'الصدر'],
        ]);
        expect($sizeGuide->rows)->toEqual([
            ['size' => '54', 'values' => [137.0, 112.0]],
            ['size' => '56', 'values' => [142.5, null]],
        ]);
        expect($sizeGuide->rows[1]['values'][1])->toBeNull();
        expect($sizeGuide->notes_ar)->toBeNull();
    });

    it('renumbers rows and columns after some were removed', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.size-guides.store'), sizeGuidePayload([
                'columns' => [2 => ['en' => 'Length', 'ar' => 'الطول']],
                'rows' => [5 => ['size' => '58', 'values' => [3 => '147']]],
            ]))
            ->assertRedirectToRoute('admin.size-guides.index');

        expect(SizeGuide::query()->sole()->rows)->toEqual([['size' => '58', 'values' => [147.0]]]);
    });

    it('rejects a row that is missing a measurement', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.size-guides.store'), sizeGuidePayload([
                'rows' => [['size' => '54', 'values' => ['137']]],
            ]))
            ->assertSessionHasErrors(['rows' => 'Row 1 needs a value for every measurement.']);

        $this->assertDatabaseCount('size_guides', 0);
    });

    it('rejects measurements that are not numbers', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.size-guides.store'), sizeGuidePayload([
                'rows' => [['size' => '54', 'values' => ['long', '112']]],
            ]))
            ->assertSessionHasErrors(['rows.0.values.0' => 'Measurements must be numbers in centimeters.']);
    });

    it('requires an Arabic name for every measurement', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.size-guides.store'), sizeGuidePayload([
                'columns' => [['en' => 'Length', 'ar' => ''], ['en' => 'Chest', 'ar' => 'الصدر']],
            ]))
            ->assertSessionHasErrors(['columns.0.ar' => 'Every measurement needs an Arabic name.']);
    });

    it('requires at least one measurement and one size', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.size-guides.store'), sizeGuidePayload(['columns' => [], 'rows' => []]))
            ->assertSessionHasErrors(['columns', 'rows']);
    });
});

describe('edit', function () {
    it('loads the saved table into the editor', function () {
        $sizeGuide = SizeGuide::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.size-guides.edit', $sizeGuide))
            ->assertOk()
            ->assertSee('sizeGuideEditor(', false)
            ->assertSee('Length');
    });
});

describe('update', function () {
    it('replaces the table', function () {
        $sizeGuide = SizeGuide::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.size-guides.update', $sizeGuide), sizeGuidePayload([
                'columns' => [['en' => 'Sleeve', 'ar' => 'الكم']],
                'rows' => [['size' => 'M', 'values' => ['60']]],
            ]))
            ->assertRedirectToRoute('admin.size-guides.index');

        $sizeGuide->refresh();
        expect($sizeGuide->columns)->toBe([['en' => 'Sleeve', 'ar' => 'الكم']]);
        expect($sizeGuide->rows)->toEqual([['size' => 'M', 'values' => [60.0]]]);
    });
});

describe('destroy', function () {
    it('deletes a size guide', function () {
        $sizeGuide = SizeGuide::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.size-guides.destroy', $sizeGuide))
            ->assertRedirectToRoute('admin.size-guides.index');

        $this->assertModelMissing($sizeGuide);
    });
});
