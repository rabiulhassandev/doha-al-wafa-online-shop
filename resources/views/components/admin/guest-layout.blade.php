@props(['title'])

@inject('settings', 'App\Services\Settings')

<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $title }} · Admin · {{ $settings->get('store_name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/admin.js'])
    </head>
    <body class="bg-motif flex min-h-dvh items-center justify-center bg-sand-100 px-4 py-12">
        <main class="w-full max-w-sm">
            <div class="text-center">
                <span class="font-brand text-3xl text-maroon-700">{{ $settings->get('store_name') }}</span>
                <h1 class="mt-2 text-sm text-ink-500">{{ $title }} to the admin panel</h1>
            </div>

            <div class="mt-8 rounded-2xl border border-sand-200 bg-white p-6 shadow-sm sm:p-8">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
