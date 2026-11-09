@props(['title', 'backUrl'])

{{-- A printable page in the current language. The toolbar is for the team and is left off the printout. --}}
@inject('settings', 'App\Services\Settings')

@php
    $locale = app()->getLocale();
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ config("shop.locales.{$locale}.direction") }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title }} · {{ $settings->get('store_name') }}</title>

        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-sand-100 text-ink-900 print:bg-white">
        <div class="mx-auto max-w-3xl p-4 sm:p-8 print:max-w-none print:p-0">
            <div class="mb-4 flex items-center gap-3 print:hidden" dir="ltr" lang="en">
                <button type="button" onclick="window.print()" class="inline-flex h-10 items-center rounded-lg bg-maroon-700 px-4 text-sm font-medium text-white hover:bg-maroon-800">Print</button>
                <a href="{{ $backUrl }}" class="text-sm text-ink-700 hover:underline">Back to the order</a>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm sm:p-10 print:rounded-none print:p-0 print:shadow-none">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
