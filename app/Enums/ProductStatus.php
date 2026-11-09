<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    /**
     * Get the label shown in the admin panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }

    /**
     * Get the admin badge color for the status.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Draft => 'maroon',
            self::Archived => 'neutral',
        };
    }

    /**
     * Get every status as select options keyed by value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
