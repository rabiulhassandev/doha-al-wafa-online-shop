<?php

namespace App\Support;

use App\Models\ProductVariant;

/**
 * A color and size as the admin order picker shows it. Expects the product, product color
 * with its color and images, and size to be loaded.
 */
final class VariantOption
{
    /**
     * @return array{id: int, label: string, sku: string, price: string, stock: int, image: string|null}
     */
    public static function from(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'label' => "{$variant->product->name_en} · {$variant->productColor->nameEn()} · {$variant->size->name_en}",
            'sku' => $variant->sku,
            'price' => Money::format($variant->effectivePrice()),
            'stock' => $variant->stock,
            'image' => $variant->productColor->images->first()?->url(400),
        ];
    }
}
