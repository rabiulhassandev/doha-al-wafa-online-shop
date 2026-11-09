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
    ],

];
