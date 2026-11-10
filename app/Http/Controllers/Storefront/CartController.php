<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Show the bag page.
     */
    public function show(Request $request, CartService $cartService): View
    {
        return view('storefront.cart.show', [
            'summary' => $cartService->summary($cartService->current($request), $cartService->deliveryZone($request), $cartService->coupon($request)),
            'deliveryZones' => DeliveryZone::query()->active()->ordered()->get(),
        ]);
    }

    /**
     * Render the slide-in bag, which the page loads when the shopper opens it.
     */
    public function drawer(Request $request, CartService $cartService): View
    {
        return view('storefront.cart.drawer', [
            'summary' => $cartService->summary($cartService->current($request), $cartService->deliveryZone($request), $cartService->coupon($request)),
        ]);
    }
}
