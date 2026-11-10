<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * "My orders": orders placed while signed in. Customers only ever see their own.
 */
class AccountOrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('storefront.account.orders', [
            'orders' => $request->user()->orders()->withSum('items', 'quantity')->latest('placed_at')->orderByDesc('id')->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id !== null && $order->user_id === $request->user()->id, 404);

        $order->load('items');

        return view('storefront.orders.thank-you', ['order' => $order, 'fromAccount' => true]);
    }
}
