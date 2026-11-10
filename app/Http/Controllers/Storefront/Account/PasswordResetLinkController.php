<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * "Forgot your password?": emails a link to choose a new one.
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('storefront.account.forgot-password');
    }

    /**
     * Send the link. The answer is the same whether or not an account uses the email, so the
     * form cannot be used to find out who has an account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['email' => ['required', 'string', 'email']],
            ['email' => __('Enter a valid email address.')],
        );

        Password::sendResetLink(['email' => Str::lower($validated['email'])]);

        return back()->with('account_status', __('If an account uses this email, we sent it a link to choose a new password.'));
    }
}
