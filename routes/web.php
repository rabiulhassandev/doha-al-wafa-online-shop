<?php

use App\Http\Controllers\Storefront\Account\AccountOrderController;
use App\Http\Controllers\Storefront\Account\AddressController;
use App\Http\Controllers\Storefront\Account\AuthenticatedSessionController;
use App\Http\Controllers\Storefront\Account\NewPasswordController;
use App\Http\Controllers\Storefront\Account\PasswordController;
use App\Http\Controllers\Storefront\Account\PasswordResetLinkController;
use App\Http\Controllers\Storefront\Account\ProfileController;
use App\Http\Controllers\Storefront\Account\RegisteredUserController;
use App\Http\Controllers\Storefront\Account\WishlistSyncController;
use App\Http\Controllers\Storefront\BuyNowController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CartCouponController;
use App\Http\Controllers\Storefront\CartDeliveryZoneController;
use App\Http\Controllers\Storefront\CartItemController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\LocaleRedirectController;
use App\Http\Controllers\Storefront\OrderConfirmationController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ProductCardController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ProductFeedController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\SearchSuggestionController;
use App\Http\Controllers\Storefront\SeoController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\ShortLinkController;
use App\Http\Controllers\Storefront\SubscriberController;
use App\Http\Controllers\Storefront\TrackOrderController;
use App\Http\Controllers\Storefront\WishlistController;
use App\Http\Middleware\ApplyCouponFromLink;
use App\Http\Middleware\CaptureAttribution;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', LocaleRedirectController::class)->name('locale.redirect');

Route::get('p/{code}', ShortLinkController::class)
    ->middleware('throttle:120,1')
    ->where('code', '[A-Za-z0-9-]+')
    ->name('short-links.redirect');
Route::get('feeds/{platform}.csv', ProductFeedController::class)
    ->middleware('throttle:30,1')
    ->name('feeds.show');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->middleware('throttle:30,1')->name('sitemap');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', array_keys(config('shop.locales')))])
    ->middleware([SetLocale::class, ApplyCouponFromLink::class, CaptureAttribution::class])
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
        Route::post('checkout', [CheckoutController::class, 'store'])
            ->middleware('throttle:place-order')
            ->name('checkout.store');
        Route::get('orders/{order:number}/thank-you', OrderConfirmationController::class)
            ->middleware('signed')
            ->name('orders.thank-you');
        Route::get('pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');
        Route::get('faq', [PageController::class, 'faq'])->name('pages.faq');
        Route::get('contact', [ContactController::class, 'show'])->name('contact.show');
        Route::post('contact', [ContactController::class, 'store'])
            ->middleware('throttle:5,10')
            ->name('contact.store');
        Route::post('subscribe', [SubscriberController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('subscribers.store');
        Route::prefix('account')->name('account.')->group(function (): void {
            Route::middleware('guest')->group(function (): void {
                Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
                Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,10')->name('register.store');
                Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
                Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
                Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
                Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,10')->name('password.email');
                Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
                Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:10,10')->name('password.store');
            });

            Route::middleware('auth')->group(function (): void {
                Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
                Route::get('/', [AccountOrderController::class, 'index'])->name('orders');
                Route::get('orders/{order:number}', [AccountOrderController::class, 'show'])->name('orders.show');
                Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
                Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
                Route::put('password', [PasswordController::class, 'update'])->name('password.update');
                Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
                Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
                Route::put('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
                Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
                Route::put('wishlist', WishlistSyncController::class)->middleware('throttle:60,1')->name('wishlist.sync');
            });
        });

        Route::get('track', [TrackOrderController::class, 'show'])->name('track.show');
        Route::post('track', [TrackOrderController::class, 'lookup'])
            ->middleware('throttle:10,1')
            ->name('track.lookup');

        Route::middleware('throttle:60,1')->group(function (): void {
            Route::post('bag/items', [CartItemController::class, 'store'])->name('cart.items.store');
            Route::patch('bag/items/{item}', [CartItemController::class, 'update'])->name('cart.items.update');
            Route::delete('bag/items/{item}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');
            Route::put('bag/delivery-area', [CartDeliveryZoneController::class, 'update'])->name('cart.delivery-zone.update');
            Route::post('bag/coupon', [CartCouponController::class, 'store'])->name('cart.coupon.store');
            Route::delete('bag/coupon', [CartCouponController::class, 'destroy'])->name('cart.coupon.destroy');
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
