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
        // Short exchange promise for the trust badges, e.g. "Exchange within 7 days".
        'exchange_promise_en' => null,
        'exchange_promise_ar' => null,
        // Ask visitors before ad trackers (added later) may load.
        'consent_bar_enabled' => false,
        // Team WhatsApp messages; null uses the built-in wording.
        'whatsapp_message_confirm_en' => null,
        'whatsapp_message_confirm_ar' => null,
        'whatsapp_message_on_the_way_en' => null,
        'whatsapp_message_on_the_way_ar' => null,
        'whatsapp_message_missed_delivery_en' => null,
        'whatsapp_message_missed_delivery_ar' => null,
        // Floating WhatsApp button on store pages, when a WhatsApp number is set.
        'whatsapp_button_enabled' => true,
        // Ad tracking. Access tokens are saved encrypted.
        'meta_pixel_id' => null,
        'meta_capi_token' => null,
        'meta_test_event_code' => null,
        'tiktok_pixel_id' => null,
        'tiktok_events_token' => null,
        'tiktok_test_event_code' => null,
        'snap_pixel_id' => null,
        'ga4_measurement_id' => null,
        // When set, catalog feeds need "?token=…".
        'feed_token' => null,
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
