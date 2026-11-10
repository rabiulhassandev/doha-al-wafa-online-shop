<?php

namespace App\Http\Middleware;

use App\Models\Coupon;
use App\Services\CartService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Influencer and campaign links such as "/ar?coupon=EID10" apply their code for the rest of the visit.
 */
class ApplyCouponFromLink
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->query('coupon');

        if (is_string($code) && $code !== '' && $request->isMethod('GET')) {
            $coupon = Coupon::findByCode($code);

            if ($coupon !== null && $coupon->availabilityProblem() === null) {
                $request->session()->put(CartService::COUPON_SESSION_KEY, $coupon->code);
                $request->session()->now('coupon_notice', __('The code :code is applied: :benefit.', ['code' => $coupon->code, 'benefit' => $coupon->benefit()]));
            }
        }

        return $next($request);
    }
}
