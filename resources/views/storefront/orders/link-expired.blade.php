{{-- Shown for an old or altered thank-you page link. --}}
@inject('settings', 'App\Services\Settings')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@php
    $whatsApp = $settings->phone('whatsapp_number');
@endphp

<x-store.layout :title="__('This link has expired')">
    <div class="page-container py-16 text-center">
        <x-icon name="alert" class="mx-auto size-10 text-ink-300" />
        <h1 class="mt-4 font-display text-3xl">{{ __('This link has expired') }}</h1>
        <p class="mx-auto mt-3 max-w-md text-ink-700">
            {{ __('For your privacy, order links only work for a while. Your order is not affected. If you need help, message us with your order number.') }}
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            @if ($whatsApp)
                <x-store.button :href="'https://wa.me/'.$whatsApp->whatsAppNumber()" target="_blank" rel="noopener">
                    <x-icon name="whatsapp" class="size-5" />
                    {{ __('Message us on WhatsApp') }}
                </x-store.button>
            @endif
            <x-store.button :href="route('shop')" variant="outline">{{ __('Continue shopping') }}</x-store.button>
        </div>
    </div>
</x-store.layout>
