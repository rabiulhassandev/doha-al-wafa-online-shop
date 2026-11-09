<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Normalizes text so shoppers find products however they spell them.
 *
 * Arabic letters with several common spellings are folded into one form: every alef to ا,
 * ى to ي, ة to ه, ؤ to و and ئ to ي. Diacritics and tatweel are removed, and Arabic digits become 0–9.
 */
final class SearchText
{
    private const LETTER_FORMS = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ى' => 'ي', 'ئ' => 'ي',
        'ة' => 'ه',
        'ؤ' => 'و',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public static function normalize(?string $text): string
    {
        $text = (string) $text;

        // Arabic diacritics (harakat, shadda, sukun, superscript alef) and the tatweel stretch character.
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text) ?? $text;
        $text = strtr($text, self::LETTER_FORMS);

        return Str::of($text)->lower()->squish()->toString();
    }
}
