<?php

use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Read a period from a query string, with 2026-03-31 (a Tuesday) as today.
 *
 * @param  array<string, string>  $query
 */
function periodFor(array $query): ReportPeriod
{
    return ReportPeriod::fromRequest(Request::create('/admin/reports', 'GET', $query), CarbonImmutable::parse('2026-03-31 15:00'));
}

it('turns each ready-made range into dates', function (string $range, string $from, string $to) {
    $period = periodFor(['range' => $range]);

    expect([$period->from->toDateTimeString(), $period->to->toDateTimeString()])->toBe(["{$from} 00:00:00", "{$to} 23:59:59"]);
})->with([
    'today' => ['today', '2026-03-31', '2026-03-31'],
    'last 7 days' => ['7d', '2026-03-25', '2026-03-31'],
    'last 30 days' => ['30d', '2026-03-02', '2026-03-31'],
    'last 90 days' => ['90d', '2026-01-01', '2026-03-31'],
    'this month' => ['this_month', '2026-03-01', '2026-03-31'],
    'last month, shorter than this one' => ['last_month', '2026-02-01', '2026-02-28'],
    'this year' => ['this_year', '2026-01-01', '2026-03-31'],
]);

it('falls back to the last 30 days for anything it cannot read', function (array $query) {
    $period = periodFor($query);

    expect([$period->preset, $period->from->toDateString(), $period->to->toDateString()])->toBe(['30d', '2026-03-02', '2026-03-31']);
})->with([
    'nothing' => [[]],
    'an unknown range' => [['range' => 'forever']],
    'a date that does not exist' => [['from' => '2026-02-31', 'to' => '2026-03-10']],
    'not a date' => [['from' => 'yesterday', 'to' => '2026-03-10']],
    'only one date' => [['from' => '2026-03-01']],
]);

it('reads chosen dates, in either order', function (array $query) {
    $period = periodFor($query);

    expect([$period->preset, $period->from->toDateString(), $period->to->toDateString()])->toBe(['custom', '2026-03-01', '2026-03-10']);
})->with([
    'in order' => [['range' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-10']],
    'reversed' => [['from' => '2026-03-10', 'to' => '2026-03-01']],
]);

it('keeps chosen dates within two years', function () {
    $period = periodFor(['from' => '2020-01-01', 'to' => '2026-03-31']);

    expect($period->days())->toBe(ReportPeriod::MAX_DAYS);
    expect($period->to->toDateString())->toBe('2026-03-31');
});

it('groups the chart so it stays readable', function (string $range, string $requested, string $expected) {
    expect(periodFor(['range' => $range, 'group' => $requested])->grouping)->toBe($expected);
})->with([
    '30 days by default' => ['30d', '', 'day'],
    '90 days by default' => ['90d', '', 'week'],
    '30 days by week on request' => ['30d', 'week', 'week'],
    'a week cannot be grouped by week' => ['7d', 'week', 'day'],
    '90 days cannot be shown by day' => ['90d', 'day', 'week'],
    'an unknown grouping' => ['30d', 'year', 'day'],
]);

it('offers only the groupings that suit the period', function () {
    expect(array_keys(periodFor(['range' => '7d'])->groupingOptions()))->toBe(['day']);
    expect(array_keys(periodFor(['range' => '30d'])->groupingOptions()))->toBe(['day', 'week']);
    expect(array_keys(periodFor(['range' => '90d'])->groupingOptions()))->toBe(['week', 'month']);
});

it('compares with the same number of days just before', function () {
    $previous = periodFor(['range' => '7d'])->previous();

    expect([$previous->from->toDateString(), $previous->to->toDateString()])->toBe(['2026-03-18', '2026-03-24']);
});

it('starts weeks on Sunday, the first one at the start of the period', function () {
    // 2026-11-04 is a Wednesday; 2026-11-08 a Sunday.
    $period = ReportPeriod::make(CarbonImmutable::parse('2026-11-04'), CarbonImmutable::parse('2026-11-24'), 'custom', 'week');

    expect(array_map(fn (CarbonImmutable $bucket): string => $bucket->toDateString(), $period->buckets()))
        ->toBe(['2026-11-04', '2026-11-08', '2026-11-15', '2026-11-22']);
    expect($period->bucketStart(CarbonImmutable::parse('2026-11-19 22:00'))->toDateString())->toBe('2026-11-15');
});

it('keeps the period in links', function () {
    expect(periodFor(['range' => '90d', 'group' => 'month'])->query())->toBe(['range' => '90d', 'group' => 'month']);
    expect(periodFor(['from' => '2026-03-01', 'to' => '2026-03-10'])->query())->toBe(['from' => '2026-03-01', 'to' => '2026-03-10', 'group' => 'day']);
});
