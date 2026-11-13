<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Tracking;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The thank-you page after placing an order, reached through a signed link.
 */
class OrderConfirmationController extends Controller
{
    public function __invoke(Request $request, Order $order, Tracking $tracking): Response
    {
        $order->load('items');

        // Only the visit straight after checkout counts as the purchase, not later visits to the link.
        $justPlaced = $request->session()->get(CheckoutController::JUST_PLACED_SESSION_KEY) === $order->number;

        return response()->view('storefront.orders.thank-you', [
            'order' => $order,
            'trackingEvent' => $justPlaced && $tracking->isEnabled() ? $tracking->purchase($order) : null,
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }
}
