<?php

namespace App\Actions;

use App\Enums\StockMovementReason;
use App\Exceptions\NotEnoughStockException;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * The only way stock changes. Locks the variant row so concurrent changes add up, and logs every change.
 */
class AdjustStock
{
    /**
     * Add (positive) or remove (negative) stock.
     *
     * @throws NotEnoughStockException when removing more than is in stock and $clampAtZero is false
     */
    public function change(
        ProductVariant $variant,
        int $quantity,
        StockMovementReason $reason,
        ?User $user = null,
        ?string $note = null,
        bool $clampAtZero = false,
        ?Order $order = null,
    ): ?StockMovement {
        if ($quantity === 0) {
            return null;
        }

        return $this->apply($variant, $reason, $user, $note, $order, function (ProductVariant $locked) use ($quantity, $clampAtZero): int {
            $newStock = $locked->stock + $quantity;

            if ($newStock < 0 && ! $clampAtZero) {
                throw new NotEnoughStockException($locked, -$quantity);
            }

            return max(0, $newStock);
        });
    }

    /**
     * Set stock to an exact count, such as after counting the shelf.
     */
    public function setTo(ProductVariant $variant, int $count, StockMovementReason $reason, ?User $user = null, ?string $note = null): ?StockMovement
    {
        return $this->apply($variant, $reason, $user, $note, null, fn (): int => max(0, $count));
    }

    /**
     * Lock the variant, work out its new stock, save it and log the difference.
     *
     * @param  Closure(ProductVariant): int  $newStockFor
     */
    private function apply(ProductVariant $variant, StockMovementReason $reason, ?User $user, ?string $note, ?Order $order, Closure $newStockFor): ?StockMovement
    {
        return DB::transaction(function () use ($variant, $reason, $user, $note, $order, $newStockFor): ?StockMovement {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->getKey());
            $newStock = $newStockFor($locked);
            $change = $newStock - $locked->stock;

            if ($change === 0) {
                return null;
            }

            $locked->stock = $newStock;
            $locked->save();

            $variant->stock = $newStock;
            $variant->syncOriginalAttribute('stock');

            return StockMovement::query()->create([
                'product_variant_id' => $locked->id,
                'sku' => $locked->sku,
                'change' => $change,
                'stock_after' => $newStock,
                'reason' => $reason,
                'note' => $note,
                'user_id' => $user?->id,
                'order_id' => $order?->id,
            ]);
        });
    }
}
