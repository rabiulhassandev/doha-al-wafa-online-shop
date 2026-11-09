<?php

use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\LocaleRedirectController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', LocaleRedirectController::class)->name('locale.redirect');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', array_keys(config('shop.locales')))])
    ->middleware(SetLocale::class)
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('home');

        if (app()->isLocal()) {
            Route::view('styleguide', 'storefront.styleguide')->name('styleguide');
        }
    });
