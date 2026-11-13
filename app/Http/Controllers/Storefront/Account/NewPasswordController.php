<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Actions\ChangePassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Choosing a new password from the emailed link.
 */
class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): Response
    {
        return response()->view('storefront.account.reset-password', ['token' => $token, 'email' => (string) $request->query('email')])
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function store(Request $request, ChangePassword $changePassword): RedirectResponse
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

        $isTeamMember = false;
        $status = Password::reset(
            [...$request->only('password', 'password_confirmation', 'token'), 'email' => Str::lower($request->string('email'))],
            function (User $user, string $password) use ($changePassword, &$isTeamMember): void {
                $isTeamMember = $user->canAccessAdmin();
                if ($isTeamMember) {
                    Validator::make(['password' => $password], ['password' => [PasswordRule::defaults()]])->validate();
                }

                $changePassword->handle($user, $password);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('This link is no longer valid. Ask for a new one below.')]);
        }

        if ($isTeamMember) {
            return to_route('admin.login')->with('status', 'Your password was changed. Sign in with your new password.');
        }

        return to_route('account.login')->with('account_status', __('Your password was changed. Sign in with your new password.'));
    }
}
