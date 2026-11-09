@push('head')
    <meta name="robots" content="noindex">
@endpush

<x-store.layout :title="__('Page not found')">
    <div class="page-container py-20 text-center">
        <x-store.motif-divider />
        <p class="mt-6 text-sm font-medium text-maroon-700" dir="ltr">404</p>
        <h1 class="mt-2 font-display text-3xl sm:text-4xl">{{ __('We couldn’t find that page') }}</h1>
        <p class="mx-auto mt-3 max-w-md text-ink-500">{{ __('It may have moved, or the product is no longer available.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <x-store.button :href="route('shop')">{{ __('Browse all products') }}</x-store.button>
            <x-store.button :href="route('home')" variant="outline">{{ __('Go to the home page') }}</x-store.button>
        </div>
    </div>
</x-store.layout>
