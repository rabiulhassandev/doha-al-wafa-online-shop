<?php

use App\Support\DeliveryEstimate;
use Carbon\CarbonImmutable;

it('counts working days and skips days off', function (string $orderedOn, int $minDays, int $maxDays, string $expectedFrom, string $expectedTo) {
    [$from, $to] = DeliveryEstimate::between(CarbonImmutable::parse($orderedOn), $minDays, $maxDays, [5]);

    expect($from->toDateString())->toBe($expectedFrom);
    expect($to->toDateString())->toBe($expectedTo);
})->with([
    // 2026-11-09 is a Monday; 2026-11-13 is a Friday.
    'within the week' => ['2026-11-09 10:00', 1, 3, '2026-11-10', '2026-11-12'],
    'across a Friday' => ['2026-11-11 10:00', 1, 3, '2026-11-12', '2026-11-15'],
    'ordered on a Friday' => ['2026-11-13 10:00', 1, 1, '2026-11-14', '2026-11-14'],
    'same day' => ['2026-11-09 10:00', 0, 0, '2026-11-09', '2026-11-09'],
]);

it('never ends the latest date before the earliest', function () {
    [$from, $to] = DeliveryEstimate::between(CarbonImmutable::parse('2026-11-09'), 3, 1, []);

    expect($to->greaterThanOrEqualTo($from))->toBeTrue();
});

it('offers the earliest delivery day and the delivery days after it', function () {
    $days = DeliveryEstimate::choosableDays(CarbonImmutable::parse('2026-11-09 10:00'), 1, 7, [5]);

    expect(array_map(fn (CarbonImmutable $day): string => $day->toDateString(), $days))
        ->toBe(['2026-11-10', '2026-11-11', '2026-11-12', '2026-11-14', '2026-11-15', '2026-11-16', '2026-11-17']);
});

it('never offers a day off, even for same-day areas', function () {
    $days = DeliveryEstimate::choosableDays(CarbonImmutable::parse('2026-11-13 10:00'), 0, 2, [5]);

    expect(array_map(fn (CarbonImmutable $day): string => $day->toDateString(), $days))->toBe(['2026-11-14', '2026-11-15']);
});

it('ignores a setting with every day off', function () {
    [$from] = DeliveryEstimate::between(CarbonImmutable::parse('2026-11-09'), 1, 1, [1, 2, 3, 4, 5, 6, 7]);

    expect($from->toDateString())->toBe('2026-11-10');
});
