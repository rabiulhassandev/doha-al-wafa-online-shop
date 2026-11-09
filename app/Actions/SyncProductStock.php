<?php

namespace App\Actions;

use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves the colors × sizes stock grid.
 *
 * Each cell sends the stock it showed when the page loaded and the stock the owner typed. Only the
 * difference is applied, so an order placed while the grid was open is not overwritten. Sizes that
 * are switched off keep their variants (and history) but stop being offered.
 */
class SyncProductStock
{
    public function __construct(
        private readonly CreateProductVariant $createVariant,
        private readonly AdjustStock $adjustStock,
    ) {}

    /**
     * @param  list<int>  $sizeIds  the sizes the product comes in
     * @param  array<int, array<int, array{stock: int, original_stock: int, offered: bool}>>  $cells  keyed by product color ID, then size ID
     */
    public function handle(Product $product, array $sizeIds, array $cells, User $user): void
    {
        DB::transaction(function () use ($product, $sizeIds, $cells, $user): void {
            $product->load(['colors.color', 'colors.product', 'variants']);
            $sizes = Size::query()->ordered()->findMany($sizeIds);
            $variants = $product->variants->keyBy(fn (ProductVariant $variant): string => "{$variant->product_color_id}:{$variant->size_id}");

            foreach ($product->colors as $productColor) {
                foreach ($sizes as $size) {
                    $cell = $cells[$productColor->id][$size->id] ?? ['stock' => 0, 'original_stock' => 0, 'offered' => true];
                    $variant = $variants->get("{$productColor->id}:{$size->id}")
                        ?? $this->createVariant->handle($productColor, $size);

                    $variant->update(['is_active' => $cell['offered']]);

                    $this->adjustStock->change(
                        $variant,
                        $cell['stock'] - $cell['original_stock'],
                        StockMovementReason::StockGrid,
                        $user,
                        clampAtZero: true,
                    );
                }
            }

            $product->variants()
                ->whereNotIn('size_id', $sizes->modelKeys())
                ->update(['is_active' => false]);
        });
    }
}
