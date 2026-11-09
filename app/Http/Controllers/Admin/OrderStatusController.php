<?php

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Exceptions\OrderUpdateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;

class OrderStatusController extends Controller
{
    /**
     * Move an order to its next status.
     */
    public function __invoke(UpdateOrderStatusRequest $request, Order $order, UpdateOrderStatus $updateOrderStatus): RedirectResponse
    {
        $status = $request->enum('status', OrderStatus::class);

        try {
            $updateOrderStatus->handle($order, $status, $request->user(), $request->validated('note'), $request->boolean('cash_collected'));
        } catch (OrderUpdateException $exception) {
            return to_route('admin.orders.show', $order)->with('error', $exception->getMessage());
        }

        $message = $status->restocks()
            ? "Order {$order->number} is now “{$status->label()}”, and its pieces are back in stock."
            : "Order {$order->number} is now “{$status->label()}”.";

        return to_route('admin.orders.show', $order)->with('status', $message);
    }
}
