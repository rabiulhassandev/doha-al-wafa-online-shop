{{-- Frequently asked questions. Expects $faqs. --}}
@inject('settings', 'App\Services\Settings')

@php
    $whatsApp = $settings->phone('whatsapp_number');
@endphp

<x-store.layout :title="__('Frequently asked questions')">
    <div class="page-container py-8">
        <x-store.breadcrumbs :items="[[__('Home'), route('home')], [__('Frequently asked questions'), null]]" />

        <div class="mx-auto mt-6 max-w-2xl">
            <h1 class="font-display text-3xl sm:text-4xl">{{ __('Frequently asked questions') }}</h1>

            @if ($faqs->isEmpty())
                <p class="mt-6 text-ink-700">{{ __('Questions and answers will appear here soon.') }}</p>
            @else
                <div class="mt-6 divide-y divide-sand-200 rounded-2xl bg-white">
                    @foreach ($faqs as $faq)
                        <details class="group px-5 py-4" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-start justify-between gap-4 font-medium [&::-webkit-details-marker]:hidden">
                                {{ $faq->translated('question') }}
                                <x-icon name="chevron-down" class="mt-0.5 size-5 shrink-0 text-ink-500 transition-transform group-open:rotate-180" />
                            </summary>
                            <div class="rich-text mt-3 text-sm text-ink-700">
                                {{ $faq->answerHtml() }}
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif

            <div class="mt-8 rounded-2xl bg-maroon-50 p-5 text-maroon-900">
                <p class="font-semibold">{{ __('Still have a question?') }}</p>
                <div class="mt-3 flex flex-wrap gap-3">
                    @if ($whatsApp)
                        <x-store.button :href="'https://wa.me/'.$whatsApp->whatsAppNumber()" target="_blank" rel="noopener">
                            <x-icon name="whatsapp" class="size-5" />
                            {{ __('Message us on WhatsApp') }}
                        </x-store.button>
                    @endif
                    <x-store.button :href="route('contact.show')" variant="outline">{{ __('Contact us') }}</x-store.button>
                </div>
            </div>
        </div>
    </div>
</x-store.layout>
