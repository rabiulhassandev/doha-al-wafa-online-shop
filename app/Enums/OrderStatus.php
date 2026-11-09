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

    /**
     * Get the statuses an order in this status may move to.
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::PendingConfirmation => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Packed, self::OutForDelivery, self::Cancelled],
            self::Packed => [self::OutForDelivery, self::Cancelled],
            self::OutForDelivery => [self::Delivered, self::DeliveryFailed],
            self::DeliveryFailed => [self::OutForDelivery, self::Cancelled],
            self::Delivered => [self::Returned],
            self::Cancelled, self::Returned => [],
        };
    }

    public function canBecome(self $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }

    /**
     * Determine whether moving an order into this status puts its pieces back in stock.
     */
    public function restocks(): bool
    {
        return $this === self::Cancelled || $this === self::Returned;
    }

    /**
     * Determine whether the order's lines may still change, which is until it leaves with the driver.
     */
    public function allowsItemChanges(): bool
    {
        return in_array($this, [self::PendingConfirmation, self::Confirmed, self::Packed], true);
    }

    /**
     * Determine whether the order is finished, one way or another.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Returned], true);
    }

    /**
     * Get the admin button label for moving an order into this status.
     */
    public function actionLabel(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Mark waiting for confirmation',
            self::Confirmed => 'Confirm order',
            self::Packed => 'Mark packed',
            self::OutForDelivery => 'Send out for delivery',
            self::Delivered => 'Mark delivered',
            self::Cancelled => 'Cancel order',
            self::DeliveryFailed => 'Delivery failed',
            self::Returned => 'Mark returned',
        };
    }

    /**
     * Get the admin badge color for the status.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'maroon',
            self::Delivered => 'success',
            self::Cancelled, self::DeliveryFailed, self::Returned => 'danger',
            default => 'neutral',
        };
    }

    /**
     * Get every status as filter options keyed by value, labelled in the current language.
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
