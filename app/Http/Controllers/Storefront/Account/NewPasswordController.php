<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Choosing a new password from the emailed link.
 */
class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('storefront.account.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:'.RegisterRequest::MIN_PASSWORD_LENGTH, 'max:100', 'confirmed'],
        ], [
            'email' => __('Enter a valid email address.'),
            'password.confirmed' => __('The two passwords do not match.'),
            'password' => __('Choose a password of at least :min characters.', ['min' => RegisterRequest::MIN_PASSWORD_LENGTH]),
        ]);

        $status = Password::reset(
            [...$request->only('password', 'password_confirmation', 'token'), 'email' => Str::lower($request->string('email'))],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('This link is no longer valid. Ask for a new one below.')]);
        }

        return to_route('account.login')->with('account_status', __('Your password was changed. Sign in with your new password.'));
    }
}
