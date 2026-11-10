{{--
    A round WhatsApp button in the bottom corner, when the owner turns it on in Settings and a WhatsApp number is set.
    Pages with their own WhatsApp button and a sticky bar (product, checkout, thank-you) leave it out.
--}}
@inject('settings', 'App\Services\Settings')

@php
    $whatsApp = $settings->get('whatsapp_button_enabled') ? $settings->phone('whatsapp_number') : null;
@endphp

@if ($whatsApp)
    <a
        href="https://wa.me/{{ $whatsApp->whatsAppNumber() }}?text={{ rawurlencode(__('Hello! I have a question.')) }}"
        target="_blank"
        rel="noopener"
        class="fixed end-4 bottom-4 z-30 inline-flex size-14 items-center justify-center rounded-full bg-success-700 text-white shadow-lg ring-4 ring-white/70 transition-transform hover:scale-105 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
        style="margin-bottom: env(safe-area-inset-bottom)"
        data-whatsapp-button
    >
        <x-icon name="whatsapp" class="size-7" />
        <span class="sr-only">{{ __('Chat with us on WhatsApp') }}</span>
    </a>
@endif
