<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CollectCashPayment;
use App\Exceptions\OrderUpdateException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;

class OrderPaymentController extends Controller
{
    /**
     * Record that the cash for an order was collected.
     */
    public function __invoke(Order $order, CollectCashPayment $collectCashPayment): RedirectResponse
    {
        try {
            $collectCashPayment->handle($order);
        } catch (OrderUpdateException $exception) {
            return to_route('admin.orders.show', $order)->with('error', $exception->getMessage());
        }

        return to_route('admin.orders.show', $order)->with('status', "Cash for order {$order->number} is marked as collected.");
    }
}
