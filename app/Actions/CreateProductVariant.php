<?php

namespace App\Actions;

use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Support\Str;

/**
 * Adds a size to a product color, with an automatic SKU such as "DW0012-NAVY-56" and no stock.
 */
class CreateProductVariant
{
    public function handle(ProductColor $productColor, Size $size, bool $isActive = true): ProductVariant
    {
        return ProductVariant::query()->create([
            'product_id' => $productColor->product_id,
            'product_color_id' => $productColor->id,
            'size_id' => $size->id,
            'sku' => $this->uniqueSku($productColor, $size),
            'is_active' => $isActive,
        ]);
    }

    /**
     * Build the SKU from the product code, color and size, adding a number if it is taken.
     */
    private function uniqueSku(ProductColor $productColor, Size $size): string
    {
        $base = Str::upper(implode('-', [
            $productColor->product->code,
            $productColor->color->slug,
            $size->slug,
        ]));
        $base = Str::limit($base, 54, '');
        $sku = $base;

        for ($suffix = 2; ProductVariant::query()->where('sku', $sku)->exists(); $suffix++) {
            $sku = "{$base}-{$suffix}";
        }

        return $sku;
    }
}
