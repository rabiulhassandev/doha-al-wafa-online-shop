<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryZoneController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderDocumentController;
use App\Http\Controllers\Admin\OrderExportController;
use App\Http\Controllers\Admin\OrderItemController;
use App\Http\Controllers\Admin\OrderNoteController;
use App\Http\Controllers\Admin\OrderPaymentController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductColorController;
use App\Http\Controllers\Admin\ProductColorPositionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductDefaultColorController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductImagePositionController;
use App\Http\Controllers\Admin\ProductStockController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShortLinkController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\Admin\SizeGuideController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\VariantSearchController;
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

    Route::middleware('can:manage-orders')->group(function (): void {
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/export', OrderExportController::class)->name('orders.export');
        Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{order:number}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{order:number}/status', OrderStatusController::class)->name('orders.status.update');
        Route::put('orders/{order:number}/payment', OrderPaymentController::class)->name('orders.payment.update');
        Route::put('orders/{order:number}/note', OrderNoteController::class)->name('orders.note.update');
        Route::get('orders/{order:number}/items', [OrderItemController::class, 'edit'])->name('orders.items.edit');
        Route::put('orders/{order:number}/items', [OrderItemController::class, 'update'])->name('orders.items.update');
        Route::get('orders/{order:number}/invoice', [OrderDocumentController::class, 'invoice'])->name('orders.invoice');
        Route::get('orders/{order:number}/packing-slip', [OrderDocumentController::class, 'packingSlip'])->name('orders.packing-slip');

        Route::get('variants/search', VariantSearchController::class)->name('variants.search');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

        Route::get('messages', [ContactMessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [ContactMessageController::class, 'show'])->name('messages.show');
        Route::delete('messages/{message}', [ContactMessageController::class, 'destroy'])->name('messages.destroy');

        Route::get('subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');
        Route::get('subscribers/export', [SubscriberController::class, 'export'])->name('subscribers.export');
        Route::delete('subscribers/{subscriber}', [SubscriberController::class, 'destroy'])->name('subscribers.destroy');
    });

    Route::middleware('can:manage-catalog')->group(function (): void {
        Route::resource('products', ProductController::class)->except('show');

        Route::prefix('products/{product}')->name('products.')->scopeBindings()->group(function (): void {
            Route::get('colors', [ProductColorController::class, 'index'])->name('colors.index');
            Route::post('colors', [ProductColorController::class, 'store'])->name('colors.store');
            Route::put('colors/{color}', [ProductColorController::class, 'update'])->name('colors.update');
            Route::delete('colors/{color}', [ProductColorController::class, 'destroy'])->name('colors.destroy');
            Route::put('colors/{color}/position', ProductColorPositionController::class)->name('colors.position');
            Route::put('colors/{color}/default', ProductDefaultColorController::class)->name('colors.default');

            Route::post('colors/{color}/images', [ProductImageController::class, 'store'])->name('colors.images.store');
            Route::delete('colors/{color}/images/{image}', [ProductImageController::class, 'destroy'])->name('colors.images.destroy');
            Route::put('colors/{color}/images/{image}/position', ProductImagePositionController::class)->name('colors.images.position');

            Route::get('stock', [ProductStockController::class, 'edit'])->name('stock.edit');
            Route::put('stock', [ProductStockController::class, 'update'])->name('stock.update');
            Route::put('variants', [ProductVariantController::class, 'update'])->name('variants.update');
        });

        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/{variant}', [StockAdjustmentController::class, 'create'])->name('inventory.adjust');
        Route::post('inventory/{variant}/adjustments', [StockAdjustmentController::class, 'store'])->name('inventory.adjustments.store');
        Route::get('stock-history', [StockMovementController::class, 'index'])->name('stock-movements.index');

        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('collections', CollectionController::class)->except('show');
        Route::resource('colors', ColorController::class)->except('show');
        Route::resource('sizes', SizeController::class)->except('show');
        Route::resource('size-guides', SizeGuideController::class)->except('show');

        Route::resource('banners', BannerController::class)->except('show');
        Route::resource('short-links', ShortLinkController::class)->except('show');
    });

    Route::middleware('can:manage-store')->group(function (): void {
        Route::get('reports', ReportController::class)->name('reports.index');
        Route::get('reports/{report}.csv', ReportExportController::class)
            ->whereIn('report', ReportExportController::REPORTS)
            ->name('reports.export');

        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::resource('staff', StaffController::class)->except('show');
        Route::resource('delivery-zones', DeliveryZoneController::class)->except('show');

        Route::resource('coupons', CouponController::class)->except('show');
        Route::resource('pages', PageController::class)->except('show');
        Route::resource('faqs', FaqController::class)->except('show');
    });
});
