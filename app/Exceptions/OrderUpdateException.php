<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use RuntimeException;

/**
 * The team asked for an order change that cannot be made. Messages are shown in the admin panel.
 */
class OrderUpdateException extends RuntimeException
{
    public static function statusNotAllowed(OrderStatus $from, OrderStatus $to): self
    {
        return new self("An order that is “{$from->label()}” cannot be changed to “{$to->label()}”. The page may be out of date, so reload it and try again.");
    }

    public static function itemsLocked(Order $order): self
    {
        return new self("Items can only change before an order goes out for delivery. Order {$order->number} is “{$order->status->label()}”.");
    }

    public static function noItemsLeft(): self
    {
        return new self('An order needs at least one item. To stop the order, cancel it instead.');
    }

    public static function notEnoughStock(ProductVariant $variant, int $wanted): self
    {
        return new self("Only {$variant->stock} of {$variant->sku} left, so {$wanted} more cannot be added.");
    }

    public static function notSold(ProductVariant $variant): self
    {
        return new self("{$variant->sku} is not for sale right now, so it cannot be added.");
    }

    public static function lineNoLongerSold(OrderItem $item): self
    {
        return new self("{$item->sku} no longer exists, so its quantity can only go down.");
    }

    public static function variantMissing(): self
    {
        return new self('That color and size no longer exists. Reload the page and try again.');
    }

    public static function nothingToCollect(Order $order): self
    {
        return new self("Order {$order->number} has no cash payment waiting to be collected.");
    }
}
