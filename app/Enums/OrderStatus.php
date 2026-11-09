<?php

namespace App\Enums;

/**
 * Where an order is in its journey. Cash on delivery orders start waiting for the team to confirm them.
 */
enum OrderStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Packed = 'packed';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case DeliveryFailed = 'delivery_failed';
    case Returned = 'returned';

    /**
     * Get the label in the current language.
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingConfirmation => __('Waiting for confirmation'),
            self::Confirmed => __('Confirmed'),
            self::Packed => __('Packed'),
            self::OutForDelivery => __('Out for delivery'),
            self::Delivered => __('Delivered'),
            self::Cancelled => __('Cancelled'),
            self::DeliveryFailed => __('Delivery failed'),
            self::Returned => __('Returned'),
        };
    }
}
