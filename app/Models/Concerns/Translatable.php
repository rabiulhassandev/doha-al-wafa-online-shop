<?php

namespace App\Models\Concerns;

/**
 * Reads attributes stored as "{name}_en" and "{name}_ar" columns.
 */
trait Translatable
{
    /**
     * Get an attribute in the current language, falling back to English when it is empty.
     */
    public function translated(string $attribute, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $fallbackLocale = config('app.fallback_locale');

        $value = $this->getAttribute("{$attribute}_{$locale}");

        return filled($value) ? $value : $this->getAttribute("{$attribute}_{$fallbackLocale}");
    }
}
