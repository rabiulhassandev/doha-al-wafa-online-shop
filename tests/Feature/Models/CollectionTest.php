<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;

it('is live when active with no schedule', function () {
    expect(Collection::factory()->make()->status())->toBe(CollectionStatus::Live);
});

it('is live between its start and end dates', function () {
    $this->freezeTime();

    $collection = Collection::factory()->make(['starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);

    expect($collection->status())->toBe(CollectionStatus::Live);
});

it('is scheduled before its start date', function () {
    $this->freezeTime();

    expect(Collection::factory()->scheduled()->make()->status())->toBe(CollectionStatus::Scheduled);
});

it('has ended after its end date', function () {
    $this->freezeTime();

    expect(Collection::factory()->ended()->make()->status())->toBe(CollectionStatus::Ended);
});

it('is hidden when switched off, whatever its schedule', function () {
    expect(Collection::factory()->hidden()->scheduled()->make()->status())->toBe(CollectionStatus::Hidden);
});
