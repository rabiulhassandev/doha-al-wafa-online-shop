<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Actions\SaveAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\AddressRequest;
use App\Models\Address;
use App\Models\DeliveryZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Saved delivery addresses, which checkout fills in.
 */
class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('storefront.account.addresses', [
            'addresses' => $request->user()->addresses()->with('deliveryZone')->get(),
            'deliveryZones' => DeliveryZone::query()->active()->ordered()->get(),
        ]);
    }

    public function store(AddressRequest $request, SaveAddress $saveAddress): RedirectResponse
    {
        $saveAddress->handle($request->user(), $request->addressAttributes(), $request->boolean('is_default'));

        return to_route('account.addresses.index')->with('account_status', __('The address was saved.'));
    }

    public function update(AddressRequest $request, Address $address, SaveAddress $saveAddress): RedirectResponse
    {
        $this->ensureOwnAddress($request, $address);

        $saveAddress->handle($request->user(), $request->addressAttributes(), $request->boolean('is_default'), $address);

        return to_route('account.addresses.index')->with('account_status', __('The address was saved.'));
    }

    public function destroy(Request $request, Address $address, SaveAddress $saveAddress): RedirectResponse
    {
        $this->ensureOwnAddress($request, $address);

        $saveAddress->delete($request->user(), $address);

        return to_route('account.addresses.index')->with('account_status', __('The address was deleted.'));
    }

    /**
     * Hide other shoppers' addresses behind a 404.
     */
    private function ensureOwnAddress(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 404);
    }
}
