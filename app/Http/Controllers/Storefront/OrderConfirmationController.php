<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;

/**
 * The thank-you page after placing an order, reached through a signed link.
 */
class OrderConfirmationController extends Controller
{
    public function __invoke(Order $order): View
    {
        $order->load('items');

        return view('storefront.orders.thank-you', ['order' => $order]);
    }
}
