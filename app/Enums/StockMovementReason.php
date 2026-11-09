<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case Received = 'received';
    case Damaged = 'damaged';
    case Recount = 'recount';
    case StockGrid = 'stock_grid';

    /**
     * Get the label shown in the stock history.
     */
    public function label(): string
    {
        return match ($this) {
            self::Received => 'New stock received',
            self::Damaged => 'Damaged or lost',
            self::Recount => 'Recount',
            self::StockGrid => 'Stock grid edit',
        };
    }

    /**
     * Get every reason as filter options keyed by value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $reason): array => [$reason->value => $reason->label()])
            ->all();
    }
}
