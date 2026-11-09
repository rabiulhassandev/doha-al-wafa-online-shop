<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Show what the shopper is ordering: the "Order now" item if there is one, otherwise the bag.
     *
     * Opening checkout from the bag ("?from=bag") drops any earlier "Order now" item.
     */
    public function show(Request $request, CartService $cartService): View|RedirectResponse
    {
        if ($request->query('from') === 'bag') {
            $request->session()->forget(BuyNowController::SESSION_KEY);
        }

        $deliveryZone = $cartService->deliveryZone($request);
        $buyNow = $request->session()->get(BuyNowController::SESSION_KEY);
        $buyNowVariant = is_array($buyNow) ? ProductVariant::query()->find($buyNow['variant_id'] ?? null) : null;

        $summary = $buyNowVariant !== null
            ? $cartService->summaryFor($buyNowVariant, (int) $buyNow['quantity'], $deliveryZone)
            : $cartService->summary($cartService->current($request), $deliveryZone);

        if ($summary->isEmpty()) {
            return to_route('cart.show');
        }

        return view('storefront.checkout.show', [
            'summary' => $summary,
            'isBuyNow' => $buyNowVariant !== null,
            'deliveryZones' => DeliveryZone::query()->active()->ordered()->get(),
        ]);
    }
}
