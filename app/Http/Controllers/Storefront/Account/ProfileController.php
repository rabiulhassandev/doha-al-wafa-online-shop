<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\ProfileRequest;
use App\Support\QatarPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The shopper's name, email and mobile number, and their password.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('storefront.account.profile', [
            'user' => $request->user(),
            'phone' => QatarPhone::tryParse($request->user()->phone)?->formatted(),
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $request->user()->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => QatarPhone::tryParse($request->validated('phone'))?->e164(),
        ]);

        return to_route('account.profile.edit')->with('account_status', __('Your details were saved.'));
    }
}
