<?php

use App\Models\DeliveryZone;
use App\Models\User;
use Database\Seeders\DeliveryZoneSeeder;

/**
 * Build a complete delivery area form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function deliveryZonePayload(array $overrides = []): array
{
    return [
        'name_en' => 'Al Wakrah',
        'name_ar' => 'الوكرة',
        'fee' => '25',
        'free_over' => '',
        'eta_min_days' => '1',
        'eta_max_days' => '3',
        'sort_order' => '10',
        'is_active' => '1',
        ...$overrides,
    ];
}

it('adds a delivery area with its fee in dirhams', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->post(route('admin.delivery-zones.store'), deliveryZonePayload(['free_over' => '400']))
        ->assertRedirectToRoute('admin.delivery-zones.index')
        ->assertSessionHas('status', '“Al Wakrah” was added.');

    $zone = DeliveryZone::query()->sole();
    expect($zone->fee)->toBe(2500);
    expect($zone->free_over)->toBe(40000);
    expect($zone->eta_max_days)->toBe(3);
});

it('rejects a latest delivery day before the earliest', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->post(route('admin.delivery-zones.store'), deliveryZonePayload(['eta_min_days' => '3', 'eta_max_days' => '1']))
        ->assertSessionHasErrors(['eta_max_days' => 'The latest delivery day cannot be before the earliest.']);

    $this->assertDatabaseCount('delivery_zones', 0);
});

it('pauses a delivery area', function () {
    $zone = DeliveryZone::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('admin.delivery-zones.update', $zone), deliveryZonePayload(['is_active' => '0']))
        ->assertRedirectToRoute('admin.delivery-zones.index');

    expect($zone->refresh()->is_active)->toBeFalse();
});

it('deletes a delivery area', function () {
    $zone = DeliveryZone::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->delete(route('admin.delivery-zones.destroy', $zone))
        ->assertRedirectToRoute('admin.delivery-zones.index');

    $this->assertModelMissing($zone);
});

it('lets only owners change delivery areas', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.delivery-zones.index'))
        ->assertForbidden();
});

it('seeds the eight municipalities without overwriting edits', function () {
    $this->seed(DeliveryZoneSeeder::class);
    DeliveryZone::query()->where('name_en', 'Doha')->update(['fee' => 1500]);

    $this->seed(DeliveryZoneSeeder::class);

    $this->assertDatabaseCount('delivery_zones', 8);
    expect(DeliveryZone::query()->where('name_en', 'Doha')->value('fee'))->toBe(1500);
});
