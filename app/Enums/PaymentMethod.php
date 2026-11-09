<?php

namespace App\Enums;

/**
 * How a customer pays. Each method is handled by the gateway listed for it in config/shop.php.
 */
enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';

    /**
     * Get the label in the current language.
     */
    public function label(): string
    {
        return match ($this) {
            self::CashOnDelivery => __('Cash on delivery'),
        };
    }

    /**
     * Get the short explanation shown at checkout.
     */
    public function description(): string
    {
        return match ($this) {
            self::CashOnDelivery => __('Pay in cash when your order arrives. Nothing to pay now.'),
        };
    }
}
