<?php

namespace App\Support;

final class SpreadsheetCsv
{
    public static function text(?string $value): ?string
    {
        // Quoting a CSV field does not stop spreadsheet software treating it as a formula.
        if ($value !== null && preg_match('/\A(?:[\t\r\n]|[\p{Z}\p{Cc}\p{Cf}]*[=+\-@＝＋－＠])/u', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
