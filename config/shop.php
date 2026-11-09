<?php

use App\Services\Payments\CashOnDeliveryGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Storefront Locales
    |--------------------------------------------------------------------------
    |
    | Every storefront URL starts with one of these locale codes. The first
    | visit to "/" picks the saved cookie, then the browser language, then
    | the default locale below.
    |
    */

    'locales' => [
        'en' => ['label' => 'English', 'direction' => 'ltr'],
        'ar' => ['label' => 'العربية', 'direction' => 'rtl'],
    ],

    'default_locale' => 'en',

    'locale_cookie' => 'locale',

    /*
    |--------------------------------------------------------------------------
    | Media Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk for uploaded catalog images. "public" serves files
    | from storage/app/public (run "php artisan storage:link" once). Point it
    | at an S3-compatible disk in production to serve images from a CDN.
    |
    */

    'media_disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Store Setting Defaults
    |--------------------------------------------------------------------------
    |
    | Values used until the owner saves their own on the admin settings page.
    |
    */

    'settings' => [
        'store_name' => 'Doha AL WAFA',
        'contact_phone' => null,
        'whatsapp_number' => null,
        'contact_email' => null,
        'instagram_url' => null,
        'tiktok_url' => null,
        'facebook_url' => null,
        'snapchat_url' => null,
        'announcement_en' => null,
        'announcement_ar' => null,
        'low_stock_threshold' => 3,
        // Dirhams; null means no free delivery offer.
        'delivery_free_over' => null,
        // ISO weekdays with no delivery: 5 = Friday.
        'delivery_days_off' => [5],
        'preferred_date_enabled' => true,
        'max_quantity_per_item' => 10,
        // New order emails go here; null sends none.
        'order_alert_email' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | The payment methods offered at checkout, in order, each with the class
    | that handles it (an App\Services\Payments\PaymentGateway). Add an online
    | gateway here once its class exists.
    |
    */

    'payment_gateways' => [
        'cash_on_delivery' => CashOnDeliveryGateway::class,
    ],

];
