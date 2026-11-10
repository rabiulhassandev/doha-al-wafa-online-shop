{{--
    Asks visitors whether ad trackers may load, when the owner turns it on in Settings.
    The answer is kept in the "tracking_consent" cookie ("accepted" or "declined") for a year.
--}}
@inject('settings', 'App\Services\Settings')

@if ($settings->get('consent_bar_enabled'))
    @php
        $privacyPage = $navigation['pages']->firstWhere('slug', 'privacy');
    @endphp

    <div
        x-data="consentBar"
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-x-3 bottom-3 z-50 mx-auto max-w-xl rounded-2xl bg-ink-900 p-4 text-sm text-white shadow-xl"
        role="region"
        aria-label="{{ __('Cookie choice') }}"
    >
        <p>
            {{ __('May we use cookies to measure our ads? It helps us show you better offers. The store works either way.') }}
            @if ($privacyPage)
                <a href="{{ route('pages.show', $privacyPage) }}" class="underline underline-offset-4">{{ $privacyPage->translated('title') }}</a>
            @endif
        </p>
        <div class="mt-3 flex gap-2">
            <button type="button" x-on:click="choose('accepted')" class="inline-flex h-10 items-center rounded-full bg-white px-5 font-medium text-ink-900 hover:bg-sand-100">{{ __('Accept') }}</button>
            <button type="button" x-on:click="choose('declined')" class="inline-flex h-10 items-center rounded-full border border-white/30 px-5 font-medium hover:bg-white/10">{{ __('No thanks') }}</button>
        </div>
    </div>
@endif
