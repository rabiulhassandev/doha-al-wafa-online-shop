<?php

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateOrderItems;
use App\Exceptions\OrderUpdateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderItemsRequest;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OrderItemController extends Controller
{
    /**
     * Show the form for changing an order's lines before it goes out for delivery.
     */
    public function edit(Order $order): View|RedirectResponse
    {
        if (! $order->status->allowsItemChanges()) {
            return to_route('admin.orders.show', $order)->with('error', OrderUpdateException::itemsLocked($order)->getMessage());
        }

        $order->load('items.productVariant');

        return view('admin.orders.items', [
            'order' => $order,
            'deliveryFee' => Money::toInput($order->delivery_fee),
        ]);
    }

    /**
     * Save the changed lines. Stock and the cash to collect follow the change.
     */
    public function update(UpdateOrderItemsRequest $request, Order $order, UpdateOrderItems $updateOrderItems): RedirectResponse
    {
        try {
            $updateOrderItems->handle($order, $request->quantities(), $request->additions(), $request->deliveryFee(), $request->user());
        } catch (OrderUpdateException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('admin.orders.show', $order)->with('status', "Order {$order->number} was updated. The total is now ".Money::format($order->grand_total).'.');
    }
}
