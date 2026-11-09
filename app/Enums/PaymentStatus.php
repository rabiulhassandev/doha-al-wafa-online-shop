<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Failed = 'failed';

    /**
     * Get the label in the current language.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Not paid yet'),
            self::Paid => __('Paid'),
            self::Refunded => __('Refunded'),
            self::Failed => __('Payment failed'),
        };
    }

    /**
     * Get the admin badge color for the status.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Refunded, self::Failed => 'danger',
            self::Pending => 'neutral',
        };
    }

    /**
     * Get every status as filter options keyed by value.
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
