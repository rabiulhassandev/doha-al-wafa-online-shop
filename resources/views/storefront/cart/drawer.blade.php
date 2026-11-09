{{-- Contents of the slide-in bag. Expects $summary, optionally $message. --}}
@if ($message ?? null)
    <p class="shrink-0 bg-success-50 px-5 py-2.5 text-sm text-success-700" role="status">{{ $message }}</p>
@endif

@if ($summary->isEmpty())
    <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
        <x-icon name="bag" class="size-10 text-ink-300" />
        <p class="mt-4 font-medium">{{ __('Your bag is empty.') }}</p>
        <x-store.button :href="route('shop')" variant="outline" class="mt-5">{{ __('Browse all products') }}</x-store.button>
    </div>
@else
    <ul class="min-h-0 flex-1 divide-y divide-sand-200 overflow-y-auto px-5">
        @foreach ($summary->lines as $line)
            @include('storefront.cart.line', ['line' => $line])
        @endforeach
    </ul>

    <div class="shrink-0 space-y-4 border-t border-sand-200 bg-white px-5 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
        @include('storefront.cart.totals', ['summary' => $summary])

        <div class="grid gap-2">
            @if ($summary->hasUnavailableLines())
                <x-store.button size="lg" disabled>{{ __('Checkout') }}</x-store.button>
                <p class="text-center text-xs text-danger-700">{{ __('Remove sold out items to continue.') }}</p>
            @else
                <x-store.button :href="route('checkout.show', ['from' => 'bag'])" size="lg">{{ __('Checkout') }}</x-store.button>
            @endif
            <x-store.button :href="route('cart.show')" variant="ghost">{{ __('View bag') }}</x-store.button>
        </div>
    </div>
@endif
