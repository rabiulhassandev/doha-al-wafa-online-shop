<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Settings::class);
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

        // Admin team routes only resolve team members, so customer accounts return 404.
        Route::bind('staff', fn (string $value): User => User::query()->staff()->findOrFail($value));
    }
}
