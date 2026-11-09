<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Settings;
use Illuminate\Contracts\View\View;

/**
 * The thank-you page after placing an order, reached through a signed link.
 */
class OrderConfirmationController extends Controller
{
    public function __invoke(Order $order, Settings $settings): View
    {
        $order->load('items');
        $whatsApp = $settings->phone('whatsapp_number');
        $message = __('Hello! I just placed order :number. Please confirm it.', ['number' => $order->number]);

        return view('storefront.orders.thank-you', [
            'order' => $order,
            'whatsAppUrl' => $whatsApp ? 'https://wa.me/'.$whatsApp->whatsAppNumber().'?text='.rawurlencode($message) : null,
        ]);
    }
}
