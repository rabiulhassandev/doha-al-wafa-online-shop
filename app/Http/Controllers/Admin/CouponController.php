<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class CouponController extends Controller
{
    /**
     * List the discount codes with how often they were used, the discount given and the sales they brought.
     */
    public function index(): View
    {
        $notCancelled = fn (Builder $query) => $query->whereHas('order', fn (Builder $query) => $query->whereNot('status', OrderStatus::Cancelled));

        $coupons = Coupon::query()
            ->withCount(['redemptions as uses_count' => $notCancelled])
            ->withSum(['redemptions as discount_given' => $notCancelled], 'discount')
            ->withSum(['orders as sales' => fn (Builder $query) => $query->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])], 'grand_total')
            ->latest()
            ->orderByDesc('id')
            ->get();

        return view('admin.coupons.index', ['coupons' => $coupons]);
    }

    /**
     * Show the form for adding a discount code.
     */
    public function create(): View
    {
        return view('admin.coupons.create', ['coupon' => new Coupon]);
    }

    /**
     * Add a discount code.
     */
    public function store(CouponRequest $request): RedirectResponse
    {
        $coupon = Coupon::query()->create($request->couponAttributes());

        return to_route('admin.coupons.index')->with('status', "The code {$coupon->code} was added.");
    }

    /**
     * Show the form for editing a discount code.
     */
    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', ['coupon' => $coupon]);
    }

    /**
     * Update a discount code.
     */
    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->couponAttributes());

        return to_route('admin.coupons.index')->with('status', "The code {$coupon->code} was updated.");
    }

    /**
     * Delete a discount code nobody has used. Used codes stay for the order history; turn them off instead.
     */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        if ($coupon->redemptions()->exists()) {
            return to_route('admin.coupons.index')->with('error', "The code {$coupon->code} was used on orders, so it stays for their history. Turn it off instead.");
        }

        $coupon->delete();

        return to_route('admin.coupons.index')->with('status', "The code {$coupon->code} was deleted.");
    }
}
