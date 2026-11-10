<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Adds, changes and removes bag lines. The slide-in bag asks for JSON with its new contents;
 * plain form posts are redirected to the bag page.
 */
class CartItemController extends Controller
{
    private const MAX_QUANTITY_INPUT = 999;

    /**
     * Add a color and size to the bag.
     */
    public function store(Request $request, CartService $cartService): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY_INPUT],
        ]);

        $variant = ProductVariant::query()->find($validated['variant_id']);

        if ($variant === null || $cartService->maxQuantity($variant) === 0) {
            throw ValidationException::withMessages(['variant_id' => __('Sorry, this size just sold out.')]);
        }

        $cart = $cartService->currentOrCreate($request);
        $result = $cartService->add($cart, $variant, (int) ($validated['quantity'] ?? 1));

        $message = $result['limited']
            ? __('Only :count of this size can be ordered, so your bag has :count.', ['count' => $result['quantity']])
            : __('Added to your bag.');

        return $this->respond($request, $cartService, $cart, $message);
    }

    /**
     * Change how many of a line are in the bag.
     */
    public function update(Request $request, CartItem $item, CartService $cartService): JsonResponse|RedirectResponse
    {
        $cart = $this->shoppersBagHolding($request, $item, $cartService);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:'.self::MAX_QUANTITY_INPUT],
        ]);

        $result = $cartService->setQuantity($item, (int) $validated['quantity']);

        $message = match (true) {
            $result['quantity'] === 0 => __('Removed from your bag.'),
            $result['limited'] => __('Only :count of this size can be ordered, so your bag has :count.', ['count' => $result['quantity']]),
            default => __('Your bag was updated.'),
        };

        return $this->respond($request, $cartService, $cart, $message);
    }

    /**
     * Remove a line from the bag.
     */
    public function destroy(Request $request, CartItem $item, CartService $cartService): JsonResponse|RedirectResponse
    {
        $cart = $this->shoppersBagHolding($request, $item, $cartService);

        $item->delete();

        return $this->respond($request, $cartService, $cart, __('Removed from your bag.'));
    }

    /**
     * Get the shopper's bag when it holds the line, hiding other shoppers' lines behind a 404.
     */
    private function shoppersBagHolding(Request $request, CartItem $item, CartService $cartService): Cart
    {
        $cart = $cartService->current($request);

        abort_unless($cart !== null && $item->cart_id === $cart->id, 404);

        return $cart;
    }

    /**
     * Answer the slide-in bag with its new contents, or send form posts to the bag page.
     */
    private function respond(Request $request, CartService $cartService, Cart $cart, string $message): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return to_route('cart.show')->with('status', $message);
        }

        $summary = $cartService->summary($cart, $cartService->deliveryZone($request), $cartService->coupon($request));

        return response()->json([
            'message' => $message,
            'count' => $summary->lines->sum('quantity'),
            'drawer' => view('storefront.cart.drawer', ['summary' => $summary, 'message' => $message])->render(),
        ]);
    }
}
