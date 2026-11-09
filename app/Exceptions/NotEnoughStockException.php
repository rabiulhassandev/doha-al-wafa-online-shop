<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use RuntimeException;

class NotEnoughStockException extends RuntimeException
{
    public function __construct(public readonly ProductVariant $variant, public readonly int $requested)
    {
        parent::__construct("Only {$variant->stock} left of {$variant->sku}; tried to remove {$requested}.");
    }
}
