<?php

namespace App\Support;

/**
 * Number input typed on Arabic keyboards.
 */
final class Digits
{
    /**
     * Eastern Arabic and Persian digits mapped to Western digits.
     */
    private const ARABIC_DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /**
     * Replace Eastern Arabic and Persian digits with Western ones, e.g. "٤٥" becomes "45".
     */
    public static function toWestern(string $value): string
    {
        return strtr($value, self::ARABIC_DIGITS);
    }
}
