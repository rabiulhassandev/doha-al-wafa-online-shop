<?php

use App\Http\Controllers\Storefront\BuyNowController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CartDeliveryZoneController;
use App\Http\Controllers\Storefront\CartItemController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\LocaleRedirectController;
use App\Http\Controllers\Storefront\ProductCardController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\SearchSuggestionController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\WishlistController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', LocaleRedirectController::class)->name('locale.redirect');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', array_keys(config('shop.locales')))])
    ->middleware(SetLocale::class)
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('home');

        Route::get('shop', ShopController::class)->name('shop');
        Route::get('category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');
        Route::get('collection/{collection:slug}', [CollectionController::class, 'show'])->name('collection.show');
        Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

        Route::get('search', SearchController::class)->name('search');
        Route::get('search/suggestions', SearchSuggestionController::class)
            ->middleware('throttle:120,1')
            ->name('search.suggestions');

        Route::get('bag', [CartController::class, 'show'])->name('cart.show');
        Route::get('bag/drawer', [CartController::class, 'drawer'])->name('cart.drawer');
        Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout.show');

        Route::middleware('throttle:60,1')->group(function (): void {
            Route::post('bag/items', [CartItemController::class, 'store'])->name('cart.items.store');
            Route::patch('bag/items/{item}', [CartItemController::class, 'update'])->name('cart.items.update');
            Route::delete('bag/items/{item}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');
            Route::put('bag/delivery-area', [CartDeliveryZoneController::class, 'update'])->name('cart.delivery-zone.update');
            Route::post('checkout/buy-now', [BuyNowController::class, 'store'])->name('checkout.buy-now');
        });

        Route::get('wishlist', WishlistController::class)->name('wishlist');
        Route::get('product-cards', ProductCardController::class)
            ->middleware('throttle:120,1')
            ->name('product-cards');

        if (app()->isLocal()) {
            Route::view('styleguide', 'storefront.styleguide')->name('styleguide');
        }
    });
