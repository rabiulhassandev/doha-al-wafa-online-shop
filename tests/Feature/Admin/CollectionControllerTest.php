<?php

use App\Models\Collection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Build a complete collection form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function collectionPayload(array $overrides = []): array
{
    return [
        'name_en' => 'Eid collection',
        'name_ar' => 'مجموعة العيد',
        'slug' => '',
        'description_en' => '',
        'description_ar' => '',
        'starts_at' => '',
        'ends_at' => '',
        'sort_order' => '0',
        'is_active' => '1',
        ...$overrides,
    ];
}

describe('index', function () {
    it('shows whether each collection is live, scheduled or ended', function () {
        $this->freezeTime();
        Collection::factory()->create(['name_en' => 'Always on', 'sort_order' => 1]);
        Collection::factory()->scheduled()->create(['name_en' => 'Coming soon', 'sort_order' => 2]);
        Collection::factory()->ended()->create(['name_en' => 'Last season', 'sort_order' => 3]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.collections.index'))
            ->assertOk()
            ->assertSeeInOrder(['Always on', 'Live', 'Coming soon', 'Scheduled', 'Last season', 'Ended']);
    });
});

describe('store', function () {
    it('creates a scheduled collection in Qatar time', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.collections.store'), collectionPayload([
                'starts_at' => '2026-12-01T09:00',
                'ends_at' => '2026-12-31T23:59',
            ]))
            ->assertRedirectToRoute('admin.collections.index')
            ->assertSessionHas('status', '“Eid collection” was created.');

        $collection = Collection::query()->sole();
        expect($collection->slug)->toBe('eid-collection');
        expect($collection->starts_at->toDateTimeString())->toBe('2026-12-01 09:00:00');
        expect($collection->starts_at->timezoneName)->toBe('Asia/Qatar');
        expect($collection->ends_at->toDateTimeString())->toBe('2026-12-31 23:59:00');
    });

    it('rejects an end date before the start date', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.collections.store'), collectionPayload([
                'starts_at' => '2026-12-10T09:00',
                'ends_at' => '2026-12-01T09:00',
            ]))
            ->assertSessionHasErrors(['ends_at' => 'The end date field must be a date after start date.']);

        $this->assertDatabaseCount('collections', 0);
    });

    it('accepts an end date without a start date', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.collections.store'), collectionPayload(['ends_at' => '2026-12-31T23:59']))
            ->assertSessionHasNoErrors();

        expect(Collection::query()->sole()->starts_at)->toBeNull();
    });

    it('stores the banner as WebP', function () {
        Storage::fake('public');

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.collections.store'), collectionPayload(['banner' => UploadedFile::fake()->image('eid.jpg', 2400, 900)]))
            ->assertRedirectToRoute('admin.collections.index');

        $bannerPath = Collection::query()->sole()->banner_path;
        expect($bannerPath)->toStartWith('collections/')->toEndWith('.webp');
        Storage::disk('public')->assertExists($bannerPath);
    });
});

describe('update', function () {
    it('clears the schedule when the dates are emptied', function () {
        $collection = Collection::factory()->scheduled()->create(['slug' => 'eid']);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.collections.update', $collection), collectionPayload(['slug' => 'eid']))
            ->assertRedirectToRoute('admin.collections.index');

        $collection->refresh();
        expect($collection->starts_at)->toBeNull();
        expect($collection->ends_at)->toBeNull();
    });
});

describe('destroy', function () {
    it('deletes a collection and its banner', function () {
        Storage::fake('public');
        Storage::disk('public')->put('collections/eid.webp', 'banner');
        $collection = Collection::factory()->create(['banner_path' => 'collections/eid.webp']);

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.collections.destroy', $collection))
            ->assertRedirectToRoute('admin.collections.index');

        $this->assertModelMissing($collection);
        Storage::disk('public')->assertMissing('collections/eid.webp');
    });
});
