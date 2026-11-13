<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Settings;
use App\Support\CanonicalUrl;
use App\Support\QatarPhone;
use App\View\Composers\AccountWishlistComposer;
use App\View\Composers\AdminNavigationComposer;
use App\View\Composers\CartCountComposer;
use App\View\Composers\StoreNavigationComposer;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Settings::class);

        $this->app->singleton(ImageManager::class, fn (): ImageManager => new ImageManager(GdDriver::class, strip: true));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        // The live store is HTTPS only, so links in pages, emails, feeds and the sitemap always use https,
        // even behind a proxy or CDN that talks to the app over plain HTTP.
        URL::forceHttps($this->app->isProduction());

        Password::defaults(fn (): Password => Password::min(10)->letters()->numbers());

        Gate::define('access-admin', fn (User $user): bool => $user->canAccessAdmin());
        Gate::define('manage-store', fn (User $user): bool => $user->isOwner());
        Gate::define('manage-catalog', fn (User $user): bool => $user->canAccessAdmin());
        Gate::define('manage-orders', fn (User $user): bool => $user->canAccessAdmin());

        $this->configureOrderRateLimits();

        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === 'web' && request()->hasSession()) {
                request()->session()->put('password_hash_web', Auth::guard('web')->hashPasswordForCookie($event->user->getAuthPassword()));
            }
        });

        // Password reset emails link to the store's own page, in the language the customer last used.
        ResetPassword::toMailUsing(fn (User $user, string $token): MailMessage => (new MailMessage)
            ->subject(__('Choose a new password'))
            ->view('mail.reset-password', [
                'user' => $user,
                'url' => CanonicalUrl::forPath(route('account.password.reset', ['locale' => $user->preferredLocale(), 'token' => $token, 'email' => $user->email], absolute: false)),
                'minutes' => config('auth.passwords.users.expire'),
            ]));

        // Admin team routes only resolve team members, so customer accounts return 404.
        Route::bind('staff', fn (string $value): User => User::query()->staff()->findOrFail($value));

        View::composer(
            ['components.store.header', 'components.store.mobile-menu', 'components.store.footer', 'components.store.consent-bar'],
            StoreNavigationComposer::class,
        );
        View::composer(['components.store.header', 'components.store.cart-drawer'], CartCountComposer::class);
        View::composer('components.admin.nav', AdminNavigationComposer::class);
        View::composer('components.store.layout', AccountWishlistComposer::class);
    }

    /**
     * Slow down fake orders: a few orders per phone number each hour, and a generous limit per
     * connection because many Qatar mobile users share one IP address.
     */
    private function configureOrderRateLimits(): void
    {
        RateLimiter::for('place-order', function (Request $request): array {
            $tooManyAttempts = fn (): RedirectResponse => to_route('checkout.show')
                ->withInput($request->except('website'))
                ->withErrors(['order' => __('Too many attempts. Please wait a few minutes and try again.')]);
            $phone = QatarPhone::tryParse((string) $request->input('phone'))?->e164() ?? $request->ip();

            return [
                Limit::perMinutes(10, 30)->by('ip:'.$request->ip())->response($tooManyAttempts),
                Limit::perHour(5)->by('phone:'.$phone)->response($tooManyAttempts),
            ];
        });
    }
}
