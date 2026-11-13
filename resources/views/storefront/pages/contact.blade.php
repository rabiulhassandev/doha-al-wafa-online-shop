{{-- Contact details and the contact form. --}}
@inject('settings', 'App\Services\Settings')

@php
    $whatsApp = $settings->phone('whatsapp_number');
    $phone = $settings->phone('contact_phone');
    $email = $settings->get('contact_email');
@endphp

<x-store.layout :title="__('Contact us')">
    <div class="page-container py-8">
        <x-store.breadcrumbs :items="[[__('Home'), route('home')], [__('Contact us'), null]]" />

        <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] lg:gap-12">
            <div>
                <h1 class="font-display text-3xl sm:text-4xl">{{ __('Contact us') }}</h1>
                <p class="mt-3 text-ink-700">{{ __('For the quickest answer, message us on WhatsApp. You can also send us a message here.') }}</p>

                <ul class="mt-6 space-y-3 text-sm">
                    @if ($whatsApp)
                        <li>
                            <x-store.button :href="'https://wa.me/'.$whatsApp->whatsAppNumber()" target="_blank" rel="noopener">
                                <x-icon name="whatsapp" class="size-5" />
                                {{ __('Message us on WhatsApp') }}
                            </x-store.button>
                        </li>
                    @endif
                    @if ($phone)
                        <li class="flex items-center gap-2">
                            <x-icon name="phone" class="size-5 text-maroon-700" />
                            <a href="tel:{{ $phone->e164() }}" class="hover:text-maroon-700" dir="ltr">{{ $phone->formatted() }}</a>
                        </li>
                    @endif
                    @if (filled($email))
                        <li class="flex items-center gap-2">
                            <x-icon name="mail" class="size-5 text-maroon-700" />
                            <a href="mailto:{{ $email }}" class="hover:text-maroon-700">{{ $email }}</a>
                        </li>
                    @endif
                </ul>

                <p class="mt-6 text-sm text-ink-500">
                    <a href="{{ route('pages.faq') }}" class="font-medium text-maroon-700 underline underline-offset-4">{{ __('Frequently asked questions') }}</a>
                    ·
                    <a href="{{ route('track.show') }}" class="font-medium text-maroon-700 underline underline-offset-4">{{ __('Track your order') }}</a>
                </p>
            </div>

            <div class="rounded-2xl bg-white p-5 sm:p-6">
                @if (session('contact_status'))
                    <div class="flex items-start gap-3 rounded-xl bg-success-50 p-4 text-sm text-success-700" role="status">
                        <x-icon name="check" class="size-5" />
                        <p>{{ session('contact_status') }}</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('contact.store') }}" novalidate class="grid gap-5" x-data="submitOnce" x-on:submit="submit($event)" x-on:pageshow.window="submitting = false">
                        @csrf
                        <input type="hidden" name="submission_token" value="{{ $errors->has('submission_token') ? $submissionToken : old('submission_token', $submissionToken) }}">
                        @error('submission_token')
                            <p class="text-sm font-medium text-danger-700" role="alert">{{ $message }}</p>
                        @enderror
                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <x-store.text-field name="name" :label="__('Your name')" autocomplete="name" maxlength="120" required />
                        <x-store.phone-field :label="__('Mobile number')" :hint="__('Or leave it empty and give your email.')" />
                        <x-store.text-field name="email" type="email" inputmode="email" autocomplete="email" maxlength="190" :label="__('Email')" :optional="true" />

                        <div>
                            <label for="message" class="block text-sm font-medium">{{ __('Your message') }}</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                maxlength="2000"
                                required
                                @if ($errors->has('message')) aria-invalid="true" aria-describedby="message-error" @endif
                                @class([
                                    'mt-1.5 block w-full rounded-xl border bg-white px-4 py-3 text-base focus:ring-2 focus:outline-none',
                                    'border-danger-700 focus:ring-danger-50' => $errors->has('message'),
                                    'border-sand-300 focus:border-maroon-700 focus:ring-maroon-100' => ! $errors->has('message'),
                                ])
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p id="message-error" class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-store.button type="submit" size="lg" class="w-full sm:w-auto" x-bind:disabled="submitting">
                            <span x-show="! submitting">{{ __('Send message') }}</span>
                            <span x-show="submitting" x-cloak>{{ __('Sending your message…') }}</span>
                        </x-store.button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-store.layout>
