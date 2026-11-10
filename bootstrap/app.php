<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Set in the browser by the cookie bar, so it is not encrypted.
        $middleware->encryptCookies(except: ['tracking_consent']);
        // The admin panel and the store each have their own sign-in page, in the store's case per language.
        $storeLocale = function (Request $request): string {
            $locale = (string) $request->segment(1);

            return array_key_exists($locale, config('shop.locales')) ? $locale : config('shop.default_locale');
        };
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('account.login', ['locale' => $storeLocale($request)]));
        $middleware->redirectUsersTo(fn (Request $request): string => $request->is('admin', 'admin/*')
            ? route('admin.dashboard')
            : route('account.orders', ['locale' => $storeLocale($request)]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Show "page not found" in the language of the URL ("/ar/…"), even when no route matched.
        $exceptions->render(function (NotFoundHttpException $exception, Request $request): null {
            $locale = (string) $request->segment(1);
            $locale = array_key_exists($locale, config('shop.locales')) ? $locale : config('shop.default_locale');

            App::setLocale($locale);
            URL::defaults(['locale' => $locale]);

            return null;
        });

        // An old or altered thank-you link explains itself instead of showing a bare 403.
        $exceptions->render(function (InvalidSignatureException $exception, Request $request): ?Response {
            return $request->routeIs('orders.thank-you')
                ? response()->view('storefront.orders.link-expired', status: 403)
                : null;
        });
    })->create();
