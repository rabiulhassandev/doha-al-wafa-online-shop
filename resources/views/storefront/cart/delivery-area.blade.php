{{-- Delivery area picker that shows the fee straight away. Expects $summary and $deliveryZones. --}}
@if ($deliveryZones->isNotEmpty())
    <form method="POST" action="{{ route('cart.delivery-zone.update') }}" class="space-y-1.5">
        @csrf
        @method('PUT')
        <label for="delivery-zone" class="block text-sm font-medium">{{ __('Delivery area') }}</label>
        <select
            id="delivery-zone"
            name="delivery_zone_id"
            x-data
            x-on:change="$el.form.requestSubmit()"
            class="h-12 w-full rounded-xl border border-sand-300 bg-white px-4 text-base focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none"
        >
            <option value="" @selected($summary->deliveryZone === null) disabled>{{ __('Choose your area') }}</option>
            @foreach ($deliveryZones as $zone)
                <option value="{{ $zone->id }}" @selected($summary->deliveryZone?->is($zone))>
                    {{ $zone->translated('name') }} — {{ $zone->feeFor($summary->subtotal(), $summary->storeFreeDeliveryThreshold) === 0 ? __('Free') : \App\Support\Money::format($zone->fee, app()->getLocale()) }}
                </option>
            @endforeach
        </select>
        <noscript><button type="submit" class="text-sm underline">{{ __('Apply') }}</button></noscript>
        @error('delivery_zone_id')
            <p class="text-xs font-medium text-danger-700">{{ $message }}</p>
        @enderror
    </form>
@endif
