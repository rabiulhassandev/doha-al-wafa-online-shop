<?php

namespace App\Enums;

enum SizeGroup: string
{
    case Letter = 'letter';
    case Length = 'length';
    case OneSize = 'one_size';

    /**
     * Get the label shown in the admin panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Letter => 'Letter (S, M, L…)',
            self::Length => 'Length (inches)',
            self::OneSize => 'One size',
        };
    }

    /**
     * Get every group as select options keyed by value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $group): array => [$group->value => $group->label()])
            ->all();
    }
}
