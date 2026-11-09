<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Settings;
use App\Support\QatarPhone;
use App\View\Composers\CartCountComposer;
use App\View\Composers\StoreNavigationComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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

        Password::defaults(fn (): Password => Password::min(10)->letters()->numbers());

        Gate::define('access-admin', fn (User $user): bool => $user->canAccessAdmin());
        Gate::define('manage-store', fn (User $user): bool => $user->isOwner());
        Gate::define('manage-catalog', fn (User $user): bool => $user->canAccessAdmin());

        $this->configureOrderRateLimits();

        // Admin team routes only resolve team members, so customer accounts return 404.
        Route::bind('staff', fn (string $value): User => User::query()->staff()->findOrFail($value));

        View::composer(
            ['components.store.header', 'components.store.mobile-menu', 'components.store.footer'],
            StoreNavigationComposer::class,
        );
        View::composer(['components.store.header', 'components.store.cart-drawer'], CartCountComposer::class);
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
