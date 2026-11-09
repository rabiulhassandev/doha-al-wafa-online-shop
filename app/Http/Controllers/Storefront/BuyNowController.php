<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * "Order now": go straight to checkout with one color and size, leaving the bag as it is.
 */
class BuyNowController extends Controller
{
    public const SESSION_KEY = 'checkout.buy_now';

    public function store(Request $request, CartService $cartService): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $variant = ProductVariant::query()->find($validated['variant_id']);
        $maxQuantity = $variant === null ? 0 : $cartService->maxQuantity($variant);

        if ($maxQuantity === 0) {
            throw ValidationException::withMessages(['variant_id' => __('Sorry, this size just sold out.')]);
        }

        $request->session()->put(self::SESSION_KEY, [
            'variant_id' => $variant->id,
            'quantity' => min((int) ($validated['quantity'] ?? 1), $maxQuantity),
        ]);

        return to_route('checkout.show');
    }
}
