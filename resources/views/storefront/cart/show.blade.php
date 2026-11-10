@push('head')
    <meta name="robots" content="noindex">
@endpush

<x-store.layout :title="__('Your bag')">
    <div class="page-container py-6">
        <x-store.breadcrumbs :items="[[__('Home'), route('home')], [__('Your bag'), null]]" />
        <h1 class="mt-4 font-display text-3xl sm:text-4xl">{{ __('Your bag') }}</h1>

        @if (session('status'))
            <p class="mt-4 rounded-xl bg-success-50 px-4 py-3 text-sm text-success-700" role="status">{{ session('status') }}</p>
        @endif
        @error('variant_id')
            <p class="mt-4 rounded-xl bg-danger-50 px-4 py-3 text-sm text-danger-700" role="alert">{{ $message }}</p>
        @enderror

        @if ($summary->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-sand-300 px-6 py-16 text-center">
                <x-icon name="bag" class="mx-auto size-10 text-ink-300" />
                <p class="mt-4 font-medium">{{ __('Your bag is empty.') }}</p>
                <x-store.button :href="route('shop')" variant="outline" class="mt-5">{{ __('Browse all products') }}</x-store.button>
            </div>
        @else
            <div class="mt-6 lg:grid lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start lg:gap-12">
                <ul class="divide-y divide-sand-200 border-y border-sand-200">
                    @foreach ($summary->lines as $line)
                        @include('storefront.cart.line', ['line' => $line])
                    @endforeach
                </ul>

                <aside class="mt-8 space-y-5 rounded-2xl bg-white p-5 lg:sticky lg:top-24 lg:mt-0">
                    @include('storefront.cart.delivery-area', ['summary' => $summary, 'deliveryZones' => $deliveryZones])
                    @include('storefront.cart.totals', ['summary' => $summary])
                    @include('storefront.cart.coupon', ['summary' => $summary])

                    @if ($summary->hasUnavailableLines())
                        <x-store.button size="lg" class="w-full" disabled>{{ __('Checkout') }}</x-store.button>
                        <p class="text-center text-xs text-danger-700">{{ __('Remove sold out items to continue.') }}</p>
                    @else
                        <x-store.button :href="route('checkout.show', ['from' => 'bag'])" size="lg" class="w-full">{{ __('Checkout') }}</x-store.button>
                    @endif
                    <x-store.button :href="route('shop')" variant="ghost" class="w-full">{{ __('Continue shopping') }}</x-store.button>
                </aside>
            </div>
        @endif
    </div>
</x-store.layout>
