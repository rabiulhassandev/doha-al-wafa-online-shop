<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Applies a discount code to the shopper's visit, for the bag and for "Order now".
 */
class CartCouponController extends Controller
{
    /**
     * Apply a code. Whether it suits the bag (such as a minimum spend) is shown in the totals.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('coupon', [
            'code' => ['required', 'string', 'max:30'],
        ], ['code.required' => __('Enter a discount code.')]);

        $coupon = Coupon::findByCode($validated['code']);
        $problem = $coupon === null
            ? __('The code :code is not valid.', ['code' => Coupon::normalizeCode($validated['code'])])
            : $coupon->availabilityProblem();

        if ($problem !== null) {
            throw ValidationException::withMessages(['code' => $problem])->errorBag('coupon');
        }

        $request->session()->put(CartService::COUPON_SESSION_KEY, $coupon->code);

        return back(fallback: route('cart.show'))
            ->with('coupon_notice', __('The code :code is applied: :benefit.', ['code' => $coupon->code, 'benefit' => $coupon->benefit()]));
    }

    /**
     * Remove the applied code.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(CartService::COUPON_SESSION_KEY);

        return back(fallback: route('cart.show'));
    }
}
