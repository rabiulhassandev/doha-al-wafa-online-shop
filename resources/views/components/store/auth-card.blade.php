@props(['title', 'intro' => null])

{{-- A narrow card for signing in, joining and resetting a password. --}}
<x-store.layout :title="$title">
    @push('head')
        <meta name="robots" content="noindex">
    @endpush

    <div class="page-container py-10">
        <div class="mx-auto max-w-md">
            <h1 class="font-display text-3xl sm:text-4xl">{{ $title }}</h1>
            @if ($intro)
                <p class="mt-2 text-ink-700">{{ $intro }}</p>
            @endif

            @if (session('account_status'))
                <p class="mt-5 flex items-start gap-2 rounded-xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700" role="status">
                    <x-icon name="check" class="size-5" />
                    {{ session('account_status') }}
                </p>
            @endif

            <div class="mt-6 rounded-2xl bg-white p-5 sm:p-6">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="mt-5 text-center text-sm text-ink-700">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</x-store.layout>
