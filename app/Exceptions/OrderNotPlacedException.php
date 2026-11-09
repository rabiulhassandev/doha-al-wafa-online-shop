<?php

namespace App\Exceptions;

use App\Support\CartLine;
use RuntimeException;

/**
 * The order could not be placed as the shopper saw it. The message is written for the shopper,
 * in their language.
 */
class OrderNotPlacedException extends RuntimeException
{
    public static function nothingToOrder(): self
    {
        return new self(__('Your bag is empty.'));
    }

    public static function blockedPhone(): self
    {
        return new self(__('We could not place this order online. Please contact us on WhatsApp to order.'));
    }

    /**
     * A color and size sold out, or has fewer left than the shopper asked for, since the page was shown.
     */
    public static function notEnoughStock(CartLine $line): self
    {
        $variant = $line->variant;
        $item = __(':product (:color, size :size)', [
            'product' => $variant->product->translated('name'),
            'color' => $variant->productColor->displayName(),
            'size' => $variant->size->translated('name'),
        ]);

        return new self($line->maxQuantity === 0
            ? __('Sorry, :item just sold out. Please check your order and try again.', ['item' => $item])
            : __('Sorry, only :count left of :item. Please check your order and try again.', ['count' => $line->maxQuantity, 'item' => $item]));
    }

    /**
     * Prices or lines changed since the page was shown, such as a bag edited in another tab.
     */
    public static function orderChanged(): self
    {
        return new self(__('Your order changed since you opened this page. Please check it and place it again.'));
    }
}
