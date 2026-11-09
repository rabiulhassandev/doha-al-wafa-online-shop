<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const COOKIE_MINUTES = 60 * 24 * 365;

    /**
     * Apply the locale from the URL prefix and remember it for the next visit.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->route('locale');

        abort_unless(array_key_exists($locale, config('shop.locales')), 404);

        App::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        // Controllers never need the prefix, so keep it out of their arguments.
        $request->route()->forgetParameter('locale');

        $cookieName = config('shop.locale_cookie');

        if ($request->cookie($cookieName) !== $locale) {
            Cookie::queue($cookieName, $locale, self::COOKIE_MINUTES);
        }

        return $next($request);
    }
}
