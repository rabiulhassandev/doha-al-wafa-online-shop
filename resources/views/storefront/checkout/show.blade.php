{{-- Checkout. Phase 4 shows the order summary and delivery fee; the details form arrives in Phase 5. --}}
@push('head')
    <meta name="robots" content="noindex">
@endpush

<x-store.layout :title="__('Checkout')">
    <div class="page-container py-6">
        <h1 class="font-display text-3xl sm:text-4xl">{{ __('Checkout') }}</h1>

        @if ($isBuyNow)
            <p class="mt-3 text-sm text-ink-700">
                {{ __('You are ordering this item only. Your bag stays as it is.') }}
                <a href="{{ route('checkout.show', ['from' => 'bag']) }}" class="font-medium text-maroon-700 underline underline-offset-4">{{ __('Order your bag instead') }}</a>
            </p>
        @endif

        <div class="mt-6 lg:grid lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start lg:gap-12">
            <div class="space-y-6">
                <section class="rounded-2xl bg-white p-5">
                    <h2 class="text-base font-semibold">{{ __('Delivery') }}</h2>
                    <div class="mt-4">
                        @include('storefront.cart.delivery-area', ['summary' => $summary, 'deliveryZones' => $deliveryZones])
                    </div>
                </section>

                <section class="rounded-2xl border border-dashed border-sand-300 p-5">
                    <h2 class="text-base font-semibold">{{ __('Your details') }}</h2>
                    <p class="mt-2 text-sm text-ink-500">{{ __('Placing orders online opens very soon. Until then, message us on WhatsApp to order.') }}</p>
                </section>
            </div>

            <aside class="mt-8 rounded-2xl bg-white p-5 lg:sticky lg:top-24 lg:mt-0">
                <h2 class="text-base font-semibold">{{ __('Order summary') }}</h2>
                <ul class="divide-y divide-sand-200">
                    @foreach ($summary->lines as $line)
                        @include('storefront.cart.line', ['line' => $line, 'readOnly' => true])
                    @endforeach
                </ul>
                <div class="border-t border-sand-200 pt-4">
                    @include('storefront.cart.totals', ['summary' => $summary])
                </div>
            </aside>
        </div>
    </div>
</x-store.layout>
