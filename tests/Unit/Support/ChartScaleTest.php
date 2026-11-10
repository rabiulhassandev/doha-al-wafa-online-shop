<?php

use App\Support\ChartScale;

it('ends the axis on a round number at or above the largest value', function (float $largest, float $axisEnd) {
    expect(ChartScale::niceMax($largest))->toBe($axisEnd);
})->with([
    'just under a round number' => [1730, 2000.0],
    'tens' => [47, 60.0],
    'already round' => [1000, 1000.0],
    'just over a round number' => [1001, 2000.0],
    'nothing yet' => [0, 10.0],
]);

it('writes axis numbers in full below 10,000 and short above', function (float $value, string $text) {
    expect(ChartScale::tick($value))->toBe($text);
})->with([
    [0, '0'],
    [2500, '2,500'],
    [20000, '20K'],
    [15000, '15K'],
    [1500000, '1.5M'],
]);
