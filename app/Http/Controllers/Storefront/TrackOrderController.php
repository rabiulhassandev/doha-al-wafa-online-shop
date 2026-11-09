<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\QatarPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Track your order": the order number and the mobile number it was placed with open the order's page.
 */
class TrackOrderController extends Controller
{
    public function show(): View
    {
        return view('storefront.orders.track');
    }

    /**
     * Open the order's page when the number and phone match. Both must match, so order numbers alone reveal nothing.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:30'],
        ], [
            'number.required' => __('Enter your order number.'),
            'phone.required' => __('Enter the mobile number you ordered with.'),
        ]);

        $order = Order::findByTypedNumber($validated['number']);
        $phone = QatarPhone::tryParse($validated['phone']);

        if ($order === null || $phone === null || $order->customer_phone !== $phone->e164()) {
            return back()->withInput()->withErrors(['number' => __('We could not find an order with this number and mobile number.')]);
        }

        return redirect($order->confirmationUrl(app()->getLocale()));
    }
}
