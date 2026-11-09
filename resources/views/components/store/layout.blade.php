@props(['title' => null, 'description' => null])

@inject('settings', 'App\Services\Settings')
@inject('localizedUrl', 'App\Support\LocalizedUrl')

@php
    $locale = app()->getLocale();
    $storeName = $settings->get('store_name');
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ config("shop.locales.{$locale}.direction") }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#fbf8f3">

        <title>{{ $title ? "{$title} · {$storeName}" : $storeName }}</title>

        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif

        @foreach (array_keys(config('shop.locales')) as $alternateLocale)
            <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $localizedUrl->current($alternateLocale) }}">
        @endforeach

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-dvh flex-col">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-ink-900 focus:px-4 focus:py-2 focus:text-sm focus:text-white">
            {{ __('Skip to content') }}
        </a>

        <x-store.announcement-bar />
        <x-store.header />

        <main id="main" class="flex-1">
            {{ $slot }}
        </main>

        <x-store.footer />
    </body>
</html>
