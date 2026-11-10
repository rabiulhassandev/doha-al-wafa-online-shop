<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Changing the account password, which needs the current one.
 */
class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:'.RegisterRequest::MIN_PASSWORD_LENGTH, 'max:100', 'confirmed'],
        ], [
            'current_password' => __('This is not your current password.'),
            'password.confirmed' => __('The two passwords do not match.'),
            'password' => __('Choose a password of at least :min characters.', ['min' => RegisterRequest::MIN_PASSWORD_LENGTH]),
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return to_route('account.profile.edit')->with('account_status', __('Your password was changed.'));
    }
}
