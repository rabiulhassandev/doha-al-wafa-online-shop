<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartDeliveryZoneController extends Controller
{
    /**
     * Remember the shopper's delivery area so the bag and checkout show its fee.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_zone_id' => ['required', 'integer', Rule::exists(DeliveryZone::class, 'id')->where('is_active', true)],
        ], [
            'delivery_zone_id.required' => __('Choose your area.'),
            'delivery_zone_id.exists' => __('Choose your area.'),
        ]);

        $request->session()->put(CartService::DELIVERY_ZONE_SESSION_KEY, (int) $validated['delivery_zone_id']);

        return back(fallback: route('cart.show'));
    }
}
