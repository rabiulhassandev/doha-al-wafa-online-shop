<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;

/**
 * Printable pages for an order, in the language the customer ordered in.
 */
class OrderDocumentController extends Controller
{
    /**
     * Show the invoice that goes to the customer.
     */
    public function invoice(Order $order): View
    {
        return $this->document('admin.orders.invoice', $order);
    }

    /**
     * Show the packing slip for whoever packs and delivers the order, with the cash to collect.
     */
    public function packingSlip(Order $order): View
    {
        return $this->document('admin.orders.packing-slip', $order);
    }

    private function document(string $view, Order $order): View
    {
        App::setLocale($order->locale);
        $order->load(['items', 'payments']);

        return view($view, ['order' => $order]);
    }
}
