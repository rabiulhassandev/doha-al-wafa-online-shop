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
    <body
        class="min-h-dvh bg-sand-100"
        x-data="{ sidebarOpen: false }"
        x-on:keydown.escape.window="sidebarOpen = false"
    >
        <div class="lg:grid lg:min-h-dvh lg:grid-cols-[16rem_minmax(0,1fr)]">
            <aside class="hidden bg-ink-900 lg:block">
                <div class="sticky top-0 h-dvh">
                    <x-admin.nav />
                </div>
            </aside>

            <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Admin menu">
                <div x-show="sidebarOpen" x-transition.opacity class="absolute inset-0 bg-ink-900/50" x-on:click="sidebarOpen = false"></div>
                <div
                    x-show="sidebarOpen"
                    x-transition:enter="transition duration-200 ease-out"
                    x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition duration-150 ease-in"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                    class="absolute inset-y-0 start-0 w-72 max-w-[85%] bg-ink-900 shadow-xl"
                >
                    <x-admin.nav />
                </div>
            </div>

            <div class="flex min-h-dvh flex-col">
                <header class="sticky top-0 z-30 flex h-14 items-center gap-2 border-b border-sand-200 bg-white px-2 lg:hidden">
                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-lg hover:bg-sand-100" x-on:click="sidebarOpen = true">
                        <x-icon name="menu" class="size-6" />
                        <span class="sr-only">Open menu</span>
                    </button>
                    <span class="font-brand text-lg text-maroon-700">{{ $settings->get('store_name') }}</span>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
                    <div class="mx-auto max-w-6xl space-y-6">
                        <x-admin.flash />

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
