<?php

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
    ],

];
