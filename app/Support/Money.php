<?php

namespace App\Support;

/**
 * Qatari riyal amounts. Prices are stored as whole dirhams (1 QAR = 100 dirhams) to avoid rounding errors.
 */
final class Money
{
    /**
     * Convert a QAR amount typed in a form, such as "185" or "185.50", to dirhams.
     */
    public static function toDirhams(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * Convert dirhams to a form value: 18500 becomes "185" and 18550 becomes "185.50".
     */
    public static function toInput(?int $dirhams): ?string
    {
        if ($dirhams === null) {
            return null;
        }

        return $dirhams % 100 === 0
            ? (string) intdiv($dirhams, 100)
            : number_format($dirhams / 100, 2, '.', '');
    }

    /**
     * Format dirhams for shoppers: "QAR 1,250" in English and "1,250 ر.ق" in Arabic.
     *
     * Whole riyals hide the decimals. Western digits are used in both languages.
     */
    public static function format(int $dirhams, string $locale = 'en'): string
    {
        $amount = $dirhams % 100 === 0
            ? number_format(intdiv($dirhams, 100))
            : number_format($dirhams / 100, 2);

        return $locale === 'ar' ? "{$amount} ر.ق" : "QAR {$amount}";
    }
}
