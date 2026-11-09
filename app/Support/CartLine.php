<?php

namespace App\Support;

use App\Enums\CartLineStatus;
use App\Models\CartItem;
use App\Models\ProductVariant;

/**
 * One color and size the shopper is about to buy, priced from the live product.
 */
final readonly class CartLine
{
    public function __construct(
        public ProductVariant $variant,
        public int $quantity,
        public int $unitPrice,
        public ?int $compareAtPrice,
        public int $maxQuantity,
        public CartLineStatus $status,
        public ?CartItem $item = null,
    ) {}

    /**
     * Get the line total in dirhams. Unavailable lines count as nothing.
     */
    public function total(): int
    {
        return $this->isOrderable() ? $this->unitPrice * $this->quantity : 0;
    }

    /**
     * Determine whether the line can be ordered.
     */
    public function isOrderable(): bool
    {
        return $this->status !== CartLineStatus::Unavailable;
    }
}
