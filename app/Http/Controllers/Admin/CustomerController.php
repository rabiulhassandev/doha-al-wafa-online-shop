<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Support\Digits;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * List customers, most recent buyers first, with what they spent on orders that were not cancelled or returned.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $blockedOnly = $request->boolean('blocked');
        $digits = preg_replace('/\D+/', '', Digits::toWestern($search));

        $customers = Customer::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->when($digits !== '', fn (Builder $query) => $query->orWhere('phone', 'like', "%{$digits}%"))))
            ->when($blockedOnly, fn (Builder $query) => $query->where('is_blocked', true))
            ->withCount('orders')
            ->withSum(['orders as spent' => fn (Builder $query) => $query->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])], 'grand_total')
            ->withMax('orders', 'placed_at')
            ->orderByDesc('orders_max_placed_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
            'blockedOnly' => $blockedOnly,
        ]);
    }

    /**
     * Show a customer with their orders.
     */
    public function show(Customer $customer): View
    {
        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $customer->orders()->withSum('items', 'quantity')->latest('placed_at')->orderByDesc('id')->paginate(20),
        ]);
    }

    /**
     * Save the customer's details, team notes and whether they may order online.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        $message = $customer->wasChanged('is_blocked')
            ? ($customer->is_blocked ? "{$customer->name} can no longer order on the website." : "{$customer->name} can order on the website again.")
            : 'Customer saved.';

        return to_route('admin.customers.show', $customer)->with('status', $message);
    }
}
