<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\OrderUpdateException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Moves an order to its next status. Cancelled and returned orders put their pieces back in stock.
 */
class UpdateOrderStatus
{
    public function __construct(
        private readonly AdjustStock $adjustStock,
        private readonly CollectCashPayment $collectCashPayment,
    ) {}

    /**
     * @param  bool  $cashCollected  when marking delivered, also record the cash as collected
     *
     * @throws OrderUpdateException when the order cannot move to the given status
     */
    public function handle(Order $order, OrderStatus $status, ?User $user, ?string $note = null, bool $cashCollected = false): void
    {
        DB::transaction(function () use ($order, $status, $user, $note, $cashCollected): void {
            // Locking the order means two team members clicking at once cannot restock it twice.
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $locked->status;

            if (! $from->canBecome($status)) {
                throw OrderUpdateException::statusNotAllowed($from, $status);
            }

            $locked->update(['status' => $status]);
            $locked->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $status,
                'note' => $note,
                'user_id' => $user?->id,
            ]);

            if ($status->restocks()) {
                $this->restock($locked, $status === OrderStatus::Cancelled ? StockMovementReason::OrderCancelled : StockMovementReason::OrderReturned, $user);
            }

            if ($status === OrderStatus::Delivered && $cashCollected && $locked->payment_status === PaymentStatus::Pending) {
                $this->collectCashPayment->handle($locked);
            }

            $order->setRawAttributes($locked->refresh()->getAttributes(), sync: true);
        });
    }

    /**
     * Put each line back in stock. Lines whose color and size was since deleted have nothing to return to.
     */
    private function restock(Order $order, StockMovementReason $reason, ?User $user): void
    {
        $items = $order->items()->whereNotNull('product_variant_id')->with('productVariant')->get();

        foreach ($items as $item) {
            $this->adjustStock->change($item->productVariant, $item->quantity, $reason, $user, $order->number, order: $order);
        }
    }
}
