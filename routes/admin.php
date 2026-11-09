<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\Admin\SizeGuideController;
use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Loaded from bootstrap/app.php with the "web" middleware, the "/admin"
| URL prefix and the "admin." route name prefix.
|
*/

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'can:access-admin'])->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware('can:manage-catalog')->group(function (): void {
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('collections', CollectionController::class)->except('show');
        Route::resource('colors', ColorController::class)->except('show');
        Route::resource('sizes', SizeController::class)->except('show');
        Route::resource('size-guides', SizeGuideController::class)->except('show');
    });

    Route::middleware('can:manage-store')->group(function (): void {
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::resource('staff', StaffController::class)->except('show');
    });
});
