@push('head')
    <meta name="robots" content="noindex">
@endpush

<x-store.layout :title="__('Wishlist')">
    <div class="page-container py-6">
        <x-store.breadcrumbs :items="[[__('Home'), route('home')], [__('Wishlist'), null]]" />
        <h1 class="mt-4 font-display text-3xl sm:text-4xl">{{ __('Wishlist') }}</h1>
        <p class="mt-2 text-sm text-ink-500">{{ auth()->check() ? __('Saved to your account, on every device. Tap the heart on any product to add it.') : __('Saved on this device. Tap the heart on any product to add it.') }}</p>

        <div x-data="productCards({ url: @js(route('product-cards')), source: 'wishlist', limit: 24 })" class="mt-8">
            <p x-show="loading" class="text-sm text-ink-500">{{ __('Loading…') }}</p>

            <div x-show="empty" x-cloak class="rounded-2xl border border-dashed border-sand-300 px-6 py-16 text-center">
                <x-icon name="heart" class="mx-auto size-8 text-ink-300" />
                <p class="mt-4 font-medium">{{ __('Your wishlist is empty.') }}</p>
                <x-store.button :href="route('shop')" variant="outline" class="mt-5">{{ __('Browse all products') }}</x-store.button>
            </div>

            <div x-ref="grid" class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4"></div>
        </div>
    </div>
</x-store.layout>
