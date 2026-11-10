<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\RegisterRequest;
use App\Models\User;
use App\Support\QatarPhone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Creates a customer account. An account is optional: guests can always order without one.
 */
class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        return view('storefront.account.register', ['next' => AuthenticatedSessionController::next($request)]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => QatarPhone::tryParse($request->validated('phone'))?->e164(),
            'locale' => app()->getLocale(),
            'password' => $request->validated('password'),
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return AuthenticatedSessionController::redirectAfterSignIn($request)
            ->with('account_status', __('Welcome, :name! Your account is ready.', ['name' => $user->name]));
    }
}
