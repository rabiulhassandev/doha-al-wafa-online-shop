<?php

namespace App\Actions;

use App\Enums\CartLineStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\NotEnoughStockException;
use App\Exceptions\OrderUpdateException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Support\Facades\DB;

/**
 * Changes an order's lines before it goes out for delivery: new quantities, removed lines and added
 * colors and sizes. Stock follows every change, and the totals and the cash to collect are worked out again.
 *
 * Lines already in the order keep the price they were sold at. Added lines get today's price.
 */
class UpdateOrderItems
{
    public function __construct(
        private readonly AdjustStock $adjustStock,
        private readonly CartService $cartService,
    ) {}

    /**
     * @param  array<int, int>  $quantities  the new quantity of each line, keyed by order item ID; 0 removes the line
     * @param  array<int, int>  $additions  pieces to add, keyed by product variant ID
     * @param  int  $deliveryFee  in dirhams
     *
     * @throws OrderUpdateException
     */
    public function handle(Order $order, array $quantities, array $additions, int $deliveryFee, User $user): void
    {
        DB::transaction(function () use ($order, $quantities, $additions, $deliveryFee, $user): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->status->allowsItemChanges()) {
                throw OrderUpdateException::itemsLocked($locked);
            }

            $items = $locked->items()->with('productVariant')->get();

            // Adding a color and size that is already in the order raises that line instead.
            foreach ($additions as $variantId => $quantity) {
                $item = $items->firstWhere('product_variant_id', $variantId);

                if ($item !== null) {
                    $quantities[$item->id] = ($quantities[$item->id] ?? $item->quantity) + $quantity;
                    unset($additions[$variantId]);
                }
            }

            foreach ($items as $item) {
                $this->changeLine($locked, $item, max(0, $quantities[$item->id] ?? $item->quantity), $user);
            }

            foreach ($additions as $variantId => $quantity) {
                $this->addLine($locked, (int) $variantId, $quantity, $user);
            }

            if (! $locked->items()->exists()) {
                throw OrderUpdateException::noItemsLeft();
            }

            $subtotal = (int) $locked->items()->sum('line_total');
            $locked->update([
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'grand_total' => $subtotal - $locked->discount_total + $deliveryFee,
            ]);
            $locked->payments()->where('status', PaymentStatus::Pending)->update(['amount' => $locked->grand_total]);

            $order->setRawAttributes($locked->getAttributes(), sync: true);
        });
    }

    private function changeLine(Order $order, OrderItem $item, int $quantity, User $user): void
    {
        $difference = $quantity - $item->quantity;

        if ($difference === 0) {
            return;
        }

        if ($item->productVariant === null && $difference > 0) {
            throw OrderUpdateException::lineNoLongerSold($item);
        }

        if ($item->productVariant !== null) {
            $this->takeStock($order, $item->productVariant, $difference, $user);
        }

        if ($quantity === 0) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => $quantity, 'line_total' => $item->unit_price * $quantity]);
    }

    private function addLine(Order $order, int $variantId, int $quantity, User $user): void
    {
        $variant = ProductVariant::query()
            ->with(['product', 'size', 'productColor.color', 'productColor.images'])
            ->find($variantId) ?? throw OrderUpdateException::variantMissing();

        $line = $this->cartService->line($variant, $quantity, withinShopperLimit: false);

        if ($line->status === CartLineStatus::Unavailable) {
            throw OrderUpdateException::notSold($variant);
        }

        if ($line->status === CartLineStatus::Reduced) {
            throw OrderUpdateException::notEnoughStock($variant, $quantity);
        }

        $order->items()->create(OrderItem::attributesFor($line));
        $this->takeStock($order, $variant, $quantity, $user);
    }

    /**
     * Take pieces from stock, or put them back when the change is negative.
     */
    private function takeStock(Order $order, ProductVariant $variant, int $pieces, User $user): void
    {
        try {
            $this->adjustStock->change($variant, -$pieces, StockMovementReason::OrderEdited, $user, $order->number, order: $order);
        } catch (NotEnoughStockException $exception) {
            throw OrderUpdateException::notEnoughStock($exception->variant, $pieces);
        }
    }
}
