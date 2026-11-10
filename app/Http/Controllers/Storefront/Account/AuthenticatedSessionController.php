<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signs shoppers in to their account and out again.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Pages a shopper can ask to come back to after signing in, with "?next=…".
     */
    private const NEXT_PAGES = ['checkout' => 'checkout.show'];

    public function create(Request $request): View
    {
        return view('storefront.account.login', ['next' => self::next($request)]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Emails such as a password reset follow the language the shopper last used.
        $request->user()->update(['locale' => app()->getLocale()]);

        return self::redirectAfterSignIn($request);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }

    /**
     * Get the page the shopper asked to come back to, if it is one of the allowed pages.
     */
    public static function next(Request $request): ?string
    {
        $next = (string) $request->input('next');

        return array_key_exists($next, self::NEXT_PAGES) ? $next : null;
    }

    /**
     * Send a shopper who just signed in to the page they asked for, the page they were stopped at, or their orders.
     */
    public static function redirectAfterSignIn(Request $request): RedirectResponse
    {
        $next = self::next($request);

        return $next !== null
            ? to_route(self::NEXT_PAGES[$next])
            : redirect()->intended(route('account.orders'));
    }
}
