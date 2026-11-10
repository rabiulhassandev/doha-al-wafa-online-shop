<?php

namespace App\Support;

/**
 * Clean axis numbers for the admin charts.
 */
final class ChartScale
{
    /**
     * Where an axis may end within each power of ten (×1, ×2, ×4…), so the middle tick is a round number too.
     */
    private const NICE_STEPS = [1, 2, 4, 6, 8, 10];

    /**
     * Round a largest value up to a clean axis end, e.g. 1,730 → 2,000 and 47 → 60. Never below the minimum.
     */
    public static function niceMax(float $value, float $minimum = 10): float
    {
        $value = max($value, $minimum);
        $magnitude = 10 ** floor(log10($value));

        foreach (self::NICE_STEPS as $step) {
            if ($step * $magnitude >= $value) {
                return $step * $magnitude;
            }
        }

        return 10 * $magnitude;
    }

    /**
     * Write an axis number: in full under 10,000, short above (12K, 1.5M).
     */
    public static function tick(float $value): string
    {
        return match (true) {
            $value >= 1_000_000 => self::trim($value / 1_000_000).'M',
            $value >= 10_000 => self::trim($value / 1_000).'K',
            default => number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 1),
        };
    }

    private static function trim(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
