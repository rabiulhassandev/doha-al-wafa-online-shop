<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Tracking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The thank-you page after placing an order, reached through a signed link.
 */
class OrderConfirmationController extends Controller
{
    public function __invoke(Request $request, Order $order, Tracking $tracking): View
    {
        $order->load('items');

        // Only the visit straight after checkout counts as the purchase, not later visits to the link.
        $justPlaced = $request->session()->get(CheckoutController::JUST_PLACED_SESSION_KEY) === $order->number;

        return view('storefront.orders.thank-you', [
            'order' => $order,
            'trackingEvent' => $justPlaced && $tracking->isEnabled() ? $tracking->purchase($order) : null,
        ]);
    }
}
