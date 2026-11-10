@props([
    'title' => null,
    'description' => null,
    // Link previews in WhatsApp, Instagram, Facebook, Snapchat and TikTok.
    'ogType' => 'website',
    'ogTitle' => null,
    'ogUrl' => null,
    'ogImage' => null,
    'ogImageAlt' => null,
    // Pages with their own WhatsApp button and a sticky bar leave out the floating one.
    'whatsAppButton' => true,
])

@inject('settings', 'App\Services\Settings')
@inject('localizedUrl', 'App\Support\LocalizedUrl')
@inject('tracking', 'App\Services\Tracking')

@php
    $locale = app()->getLocale();
    $storeName = $settings->get('store_name');
    $ogLocales = ['en' => 'en_US', 'ar' => 'ar_AR'];
    $trackingConfig = $tracking->browserConfig();
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ config("shop.locales.{$locale}.direction") }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#fbf8f3">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? "{$title} · {$storeName}" : $storeName }}</title>

        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif

        @foreach (array_keys(config('shop.locales')) as $alternateLocale)
            <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $localizedUrl->current($alternateLocale) }}">
        @endforeach

        <meta property="og:site_name" content="{{ $storeName }}">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $ogTitle ?? $title ?? $storeName }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        <meta property="og:url" content="{{ $ogUrl ?? url()->current() }}">
        <meta property="og:locale" content="{{ $ogLocales[$locale] ?? $locale }}">
        @foreach (array_diff_key($ogLocales, [$locale => true]) as $alternateOgLocale)
            <meta property="og:locale:alternate" content="{{ $alternateOgLocale }}">
        @endforeach
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
            @if ($ogImageAlt)
                <meta property="og:image:alt" content="{{ $ogImageAlt }}">
            @endif
            <meta name="twitter:card" content="summary_large_image">
        @else
            <meta name="twitter:card" content="summary">
        @endif

        @stack('head')

        @if ($trackingConfig)
            {{-- Read by resources/js/store/tracking.js, which loads the pixels once the visitor allows them. --}}
            <script type="application/json" id="tracking-config">@json($trackingConfig)</script>
        @endif

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-dvh flex-col">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-60 focus:rounded-full focus:bg-ink-900 focus:px-4 focus:py-2 focus:text-sm focus:text-white">
            {{ __('Skip to content') }}
        </a>

        <x-store.announcement-bar />
        @if (session('coupon_notice'))
            <p class="bg-success-50 px-4 py-2 text-center text-sm font-medium text-success-700" role="status">{{ session('coupon_notice') }}</p>
        @endif
        <x-store.header />
        {{-- Kept outside the header so these full-screen overlays cover the whole viewport. --}}
        <x-store.mobile-menu />
        <x-store.search-overlay />
        <x-store.cart-drawer />

        <main id="main" class="flex-1">
            {{ $slot }}
        </main>

        <x-store.footer />
        @if ($whatsAppButton)
            <x-store.whatsapp-button />
        @endif
        <x-store.consent-bar />

        @if ($accountWishlist)
            {{-- The wishlist saved on the account; the browser's wishlist merges with it (see store/wishlist.js). --}}
            <div id="wishlist-account" hidden data-url="{{ $accountWishlist['url'] }}" data-ids="@json($accountWishlist['ids'])"></div>
        @endif
    </body>
</html>
