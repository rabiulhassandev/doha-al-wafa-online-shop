<?php

namespace App\Rules;

use App\Support\QatarPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class QatarPhoneNumber implements ValidationRule
{
    public function __construct(private readonly bool $mobileOnly = false) {}

    /**
     * Create a rule that only accepts Qatar mobile numbers.
     */
    public static function mobile(): self
    {
        return new self(mobileOnly: true);
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = is_string($value) ? QatarPhone::tryParse($value) : null;

        if ($phone === null) {
            $fail('The :attribute must be a valid Qatar phone number.')->translate();

            return;
        }

        if ($this->mobileOnly && ! $phone->isMobile()) {
            $fail('The :attribute must be a Qatar mobile number.')->translate();
        }
    }
}
