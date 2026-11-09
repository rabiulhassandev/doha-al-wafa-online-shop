<?php

namespace App\Support;

final class QatarPhone
{
    private const COUNTRY_CODE = '974';

    private function __construct(private readonly string $nationalNumber) {}

    /**
     * Parse a Qatar phone number written in any common format.
     *
     * Accepts local numbers ("5555 1234"), international forms ("+974 5555 1234",
     * "00974 5555 1234") and Arabic digits. Returns null for anything else.
     */
    public static function tryParse(?string $input): ?self
    {
        $digits = preg_replace('/\D+/', '', Digits::toWestern((string) $input));

        if (str_starts_with($digits, '00'.self::COUNTRY_CODE)) {
            $digits = substr($digits, 5);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = substr($digits, 3);
        }

        // Qatar numbers have 8 digits: mobiles start with 3, 5, 6 or 7 and landlines with 4.
        if (preg_match('/^[3-7]\d{7}$/', $digits) !== 1) {
            return null;
        }

        return new self($digits);
    }

    /**
     * Determine whether the number belongs to a mobile line.
     */
    public function isMobile(): bool
    {
        return in_array($this->nationalNumber[0], ['3', '5', '6', '7'], true);
    }

    /**
     * Get the number in E.164 format, e.g. "+97455551234".
     */
    public function e164(): string
    {
        return '+'.self::COUNTRY_CODE.$this->nationalNumber;
    }

    /**
     * Get the number formatted for display, e.g. "+974 5555 1234".
     */
    public function formatted(): string
    {
        return '+'.self::COUNTRY_CODE.' '.substr($this->nationalNumber, 0, 4).' '.substr($this->nationalNumber, 4);
    }

    /**
     * Get the number in the digits-only form used by wa.me links, e.g. "97455551234".
     */
    public function whatsAppNumber(): string
    {
        return self::COUNTRY_CODE.$this->nationalNumber;
    }
}
