<?php

use App\Support\Money;

it('converts typed QAR amounts to dirhams', function (string|int|float $amount, int $dirhams) {
    expect(Money::toDirhams($amount))->toBe($dirhams);
})->with([
    'whole riyals' => ['185', 18500],
    'with dirhams' => ['185.50', 18550],
    'one decimal' => ['185.5', 18550],
    'float rounding' => [185.55, 18555],
    'zero' => [0, 0],
]);

it('converts dirhams to form values', function (?int $dirhams, ?string $input) {
    expect(Money::toInput($dirhams))->toBe($input);
})->with([
    'whole riyals' => [18500, '185'],
    'with dirhams' => [18550, '185.50'],
    'empty' => [null, null],
]);

it('formats prices for shoppers in each language', function (int $dirhams, string $locale, string $formatted) {
    expect(Money::format($dirhams, $locale))->toBe($formatted);
})->with([
    'english whole' => [18500, 'en', 'QAR 185'],
    'english thousands' => [125000, 'en', 'QAR 1,250'],
    'english dirhams' => [18550, 'en', 'QAR 185.50'],
    'arabic whole' => [18500, 'ar', '185 ر.ق'],
]);
