<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderNoteController extends Controller
{
    /**
     * Save the note only the team sees.
     */
    public function __invoke(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['admin_note' => 'team note']);

        $order->update(['admin_note' => $validated['admin_note']]);

        return to_route('admin.orders.show', $order)->with('status', 'Team note saved.');
    }
}
