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
}
