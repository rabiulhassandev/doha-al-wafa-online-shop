<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleRedirectController extends Controller
{
    /**
     * Send the visitor to the home page in their saved or preferred language.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $defaultLocale = config('shop.default_locale');
        $supportedLocales = array_values(array_unique([$defaultLocale, ...array_keys(config('shop.locales'))]));
        $savedLocale = $request->cookie(config('shop.locale_cookie'));

        $locale = in_array($savedLocale, $supportedLocales, true)
            ? $savedLocale
            : $request->getPreferredLanguage($supportedLocales);

        return redirect()
            ->route('home', ['locale' => $locale])
            ->header('Vary', 'Accept-Language, Cookie');
    }
}
