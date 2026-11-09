<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\OrderUpdateException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Records that the driver collected the cash for a cash on delivery order.
 */
class CollectCashPayment
{
    /**
     * @throws OrderUpdateException when there is no cash payment waiting, or the order has not left yet
     */
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $payment = $order->payments()
                ->where('method', PaymentMethod::CashOnDelivery)
                ->where('status', PaymentStatus::Pending)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($payment === null || ! in_array($order->status, [OrderStatus::OutForDelivery, OrderStatus::Delivered], true)) {
                throw OrderUpdateException::nothingToCollect($order);
            }

            $payment->update(['status' => PaymentStatus::Paid, 'amount' => $order->grand_total, 'paid_at' => now()]);
            $order->update(['payment_status' => PaymentStatus::Paid]);
        });
    }
}
