{{--
    Single-page cash on delivery checkout: contact details, blue plate address, optional extras and payment.
    The delivery fee and total follow the chosen area without reloading, and the browser keeps a draft of the form.
--}}
@push('head')
    <meta name="robots" content="noindex">
@endpush

@php
    use App\Support\Money;

    $locale = app()->getLocale();
    $selectedZoneId = (int) old('delivery_zone_id', $summary->deliveryZone?->id);
    $selectedZone = $zoneOptions[$selectedZoneId] ?? null;
    $optionalFields = ['email', 'unit', 'landmark', 'latitude', 'longitude', 'preferred_delivery_date', 'note'];
    $moreDetailsOpen = $errors->hasAny($optionalFields)
        || collect($optionalFields)->contains(fn (string $field): bool => filled(old($field)));
    $selectedPaymentMethod = old('payment_method', $paymentMethods[0]->value);
    $discountText = $summary->discount() > 0 ? '−'.Money::format($summary->discount(), $locale) : $summary->coupon?->benefit();
    $fieldClasses = 'mt-1.5 block w-full rounded-xl border border-sand-300 bg-white px-4 text-base focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none';
    $data = [
        'zones' => (object) $zoneOptions,
        'zoneId' => $selectedZone ? (string) $selectedZoneId : '',
        'preferredDate' => (string) old('preferred_delivery_date', ''),
        'latitude' => (string) old('latitude', ''),
        'longitude' => (string) old('longitude', ''),
        // Restore the browser draft unless this page shows the form's own submitted values.
        'restoreDraft' => old('checkout_token') === null,
        'discountText' => $discountText,
        'subtotalText' => Money::format($summary->subtotal(), $locale),
        'messages' => [
            'chooseArea' => __('Choose your area'),
            'total' => __('Total'),
            'totalBeforeDelivery' => __('Total before delivery'),
            'locationFailed' => __('We could not read your location. Please try again.'),
            'locationUnsupported' => __('Your browser cannot share its location.'),
        ],
    ];
@endphp

<x-store.layout :title="__('Checkout')">
    <div class="page-container py-6">
        <h1 class="font-display text-3xl sm:text-4xl">{{ __('Checkout') }}</h1>

        @if ($isBuyNow)
            <p class="mt-3 text-sm text-ink-700">
                {{ __('You are ordering this item only. Your bag stays as it is.') }}
                <a href="{{ route('checkout.show', ['from' => 'bag']) }}" class="font-medium text-maroon-700 underline underline-offset-4">{{ __('Order your bag instead') }}</a>
            </p>
        @endif

        @if ($errors->has('order'))
            <p class="mt-4 rounded-xl bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700" role="alert">{{ $errors->first('order') }}</p>
        @elseif ($errors->any())
            <p class="mt-4 rounded-xl bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700" role="alert">{{ __('Please check the fields marked below.') }}</p>
        @endif

        <form
            method="POST"
            action="{{ route('checkout.store') }}"
            novalidate
            x-data="checkoutForm(@js($data))"
            x-on:submit="submit($event)"
            x-on:input.debounce.400ms="saveDraft()"
            x-on:change="saveDraft()"
            x-on:pageshow.window="submitting = false"
            class="mt-6 lg:grid lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start lg:gap-12"
        >
            @csrf
            <input type="hidden" name="checkout_token" value="{{ old('checkout_token', $checkoutToken) }}">
            <input type="hidden" name="expected_subtotal" value="{{ $summary->subtotal() }}">
            {{-- Left empty by people; bots that fill every field are turned away. --}}
            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="space-y-5">
                <section class="rounded-2xl bg-white p-5 sm:p-6" aria-labelledby="checkout-details">
                    <h2 id="checkout-details" class="flex items-center gap-3 text-base font-semibold">
                        <span class="flex size-7 items-center justify-center rounded-full bg-maroon-700 text-sm text-white" aria-hidden="true">1</span>
                        {{ __('Your details') }}
                    </h2>
                    <div class="mt-5 grid gap-5">
                        <x-store.text-field name="name" :label="__('Full name')" autocomplete="name" maxlength="120" required />

                        <x-store.phone-field :label="__('Mobile number')" :hint="__('We will call or WhatsApp you on this number to confirm your order.')" required />
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-5 sm:p-6" aria-labelledby="checkout-address">
                    <h2 id="checkout-address" class="flex items-center gap-3 text-base font-semibold">
                        <span class="flex size-7 items-center justify-center rounded-full bg-maroon-700 text-sm text-white" aria-hidden="true">2</span>
                        {{ __('Delivery address') }}
                    </h2>
                    <div class="mt-5 grid gap-5">
                        <div>
                            <label for="delivery_zone_id" class="block text-sm font-medium">{{ __('Delivery area') }}</label>
                            <select
                                id="delivery_zone_id"
                                name="delivery_zone_id"
                                x-model="zoneId"
                                required
                                @if ($errors->has('delivery_zone_id')) aria-invalid="true" aria-describedby="delivery_zone_id-error" @endif
                                class="{{ $fieldClasses }} h-12"
                            >
                                <option value="" disabled @selected($selectedZone === null)>{{ __('Choose your area') }}</option>
                                @foreach ($deliveryZones as $zone)
                                    <option value="{{ $zone->id }}" @selected($selectedZoneId === $zone->id)>
                                        {{ $zone->translated('name') }} — {{ $zoneOptions[$zone->id]['feeText'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('delivery_zone_id')
                                <p id="delivery_zone_id-error" class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <fieldset>
                            <legend class="text-sm font-medium">{{ __('Blue plate numbers') }}</legend>
                            <p class="mt-1 text-xs text-ink-500">{{ __('You will find them on the blue plate on your building.') }}</p>
                            <div class="mt-3 grid grid-cols-3 gap-3">
                                <x-store.text-field name="zone_number" :label="__('Zone')" inputmode="numeric" maxlength="4" autocomplete="off" required />
                                <x-store.text-field name="street_number" :label="__('Street')" inputmode="numeric" maxlength="4" autocomplete="off" required />
                                <x-store.text-field name="building_number" :label="__('Building')" inputmode="numeric" maxlength="4" autocomplete="off" required />
                            </div>
                        </fieldset>
                    </div>
                </section>

                <details class="group rounded-2xl bg-white" @if ($moreDetailsOpen) open @endif>
                    <summary class="flex cursor-pointer list-none items-center gap-3 p-5 text-base font-semibold sm:p-6 [&::-webkit-details-marker]:hidden">
                        <span class="flex size-7 items-center justify-center rounded-full bg-sand-200 text-ink-700" aria-hidden="true">
                            <x-icon name="plus" class="size-4 transition-transform group-open:rotate-45" />
                        </span>
                        {{ __('Add more details') }}
                        <span class="text-sm font-normal text-ink-500">{{ __('(optional)') }}</span>
                    </summary>

                    <div class="grid gap-5 px-5 pb-5 sm:px-6 sm:pb-6">
                        <x-store.text-field name="unit" :label="__('Villa, apartment or floor')" maxlength="60" autocomplete="address-line2" />
                        <x-store.text-field name="landmark" :label="__('Landmark or directions')" :hint="__('For example: next to the mosque, white gate.')" maxlength="200" />

                        <div x-cloak>
                            <p class="text-sm font-medium">{{ __('Location pin') }}</p>
                            <input type="hidden" name="latitude" x-bind:value="latitude">
                            <input type="hidden" name="longitude" x-bind:value="longitude">
                            <div class="mt-1.5" x-show="! latitude">
                                <x-store.button variant="outline" size="sm" x-on:click="locate()" x-bind:disabled="locating">
                                    <x-icon name="map-pin" class="size-4" />
                                    <span x-text="locating ? @js(__('Finding you…')) : @js(__('Share my current location'))">{{ __('Share my current location') }}</span>
                                </x-store.button>
                            </div>
                            <p class="mt-1.5 flex items-center gap-2 text-sm font-medium text-success-700" x-show="latitude">
                                <x-icon name="check" class="size-4" />
                                {{ __('Location added') }}
                                <button type="button" class="text-xs font-normal text-ink-500 underline underline-offset-4" x-on:click="clearLocation()">{{ __('Remove') }}</button>
                            </p>
                            <p class="mt-1.5 text-xs text-ink-500">{{ __('Share it only if you are at the delivery address now. It helps the driver find you.') }}</p>
                            <p class="mt-1.5 text-xs font-medium text-danger-700" x-show="locationError" x-text="locationError" role="alert"></p>
                            @error('latitude')
                                <p class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-store.text-field name="email" type="email" inputmode="email" autocomplete="email" maxlength="190" :label="__('Email')" :hint="__('We will email you a copy of your order.')" />

                        @if ($preferredDateEnabled)
                            <div>
                                <label for="preferred_delivery_date" class="block text-sm font-medium">{{ __('Preferred delivery date') }}</label>
                                <select
                                    id="preferred_delivery_date"
                                    name="preferred_delivery_date"
                                    x-on:change="preferredDate = $event.target.value"
                                    @if ($errors->has('preferred_delivery_date')) aria-invalid="true" aria-describedby="preferred_delivery_date-error" @endif
                                    class="{{ $fieldClasses }} h-12"
                                >
                                    <option value="">{{ __('As soon as possible') }}</option>
                                    <template x-for="day in dates" :key="day.value">
                                        <option x-bind:value="day.value" x-text="day.label" x-bind:selected="day.value === preferredDate"></option>
                                    </template>
                                </select>
                                <p class="mt-1.5 text-xs text-ink-500" x-show="! zoneId" x-cloak>{{ __('Choose your area first to see the dates.') }}</p>
                                @error('preferred_delivery_date')
                                    <p id="preferred_delivery_date-error" class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div>
                            <label for="note" class="block text-sm font-medium">{{ __('Note for us') }}</label>
                            <textarea id="note" name="note" rows="3" maxlength="500" class="{{ $fieldClasses }} py-3">{{ old('note') }}</textarea>
                            @error('note')
                                <p class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </details>

                <section class="rounded-2xl bg-white p-5 sm:p-6" aria-labelledby="checkout-payment">
                    <h2 id="checkout-payment" class="flex items-center gap-3 text-base font-semibold">
                        <span class="flex size-7 items-center justify-center rounded-full bg-maroon-700 text-sm text-white" aria-hidden="true">3</span>
                        {{ __('Payment') }}
                    </h2>
                    <div class="mt-5 grid gap-3">
                        @foreach ($paymentMethods as $method)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-sand-300 p-4 has-checked:border-maroon-700 has-checked:bg-maroon-50">
                                <input type="radio" name="payment_method" value="{{ $method->value }}" @checked($selectedPaymentMethod === $method->value) class="mt-0.5 size-4 accent-maroon-700">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold">{{ $method->label() }}</span>
                                    <span class="mt-0.5 block text-sm text-ink-500">{{ $method->description() }}</span>
                                </span>
                                <x-icon name="cash" class="size-6 text-maroon-700" />
                            </label>
                        @endforeach
                        @error('payment_method')
                            <p class="text-xs font-medium text-danger-700">{{ $message }}</p>
                        @enderror
                    </div>
                </section>
            </div>

            <aside class="mt-5 rounded-2xl bg-white p-5 sm:p-6 lg:sticky lg:top-24 lg:mt-0">
                <h2 class="text-base font-semibold">{{ __('Order summary') }}</h2>
                <ul class="divide-y divide-sand-200">
                    @foreach ($summary->lines as $line)
                        @include('storefront.cart.line', ['line' => $line, 'readOnly' => true])
                    @endforeach
                </ul>

                <dl class="space-y-2 border-t border-sand-200 pt-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-700">{{ __('Subtotal') }}</dt>
                        <dd class="font-medium tabular-nums">{{ Money::format($summary->subtotal(), $locale) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-700">{{ __('Delivery') }}</dt>
                        <dd class="font-medium tabular-nums" x-text="deliveryText">{{ $selectedZone['feeText'] ?? __('Choose your area') }}</dd>
                    </div>
                    @if ($summary->coupon && $summary->couponProblem === null)
                        <div class="flex justify-between gap-4 text-success-700">
                            <dt>{{ __('Discount') }} <span dir="ltr">({{ $summary->coupon->code }})</span></dt>
                            <dd class="font-medium tabular-nums" x-text="zone?.discountText ?? discountText">{{ $selectedZone['discountText'] ?? $discountText }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4 border-t border-sand-200 pt-3 text-base">
                        <dt class="font-semibold" x-text="zone ? messages.total : messages.totalBeforeDelivery">{{ $selectedZone ? __('Total') : __('Total before delivery') }}</dt>
                        <dd class="font-semibold tabular-nums" x-text="totalText">{{ $selectedZone['totalText'] ?? Money::format($summary->subtotal(), $locale) }}</dd>
                    </div>
                </dl>

                <div class="mt-4 border-t border-sand-200 pt-4">
                    @include('storefront.cart.coupon', ['summary' => $summary, 'external' => true])
                </div>

                <x-store.button type="submit" size="lg" class="mt-5 w-full" x-bind:disabled="submitting">
                    <span x-show="! submitting">{{ __('Place order') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('Placing your order…') }}</span>
                </x-store.button>
                <p class="mt-3 flex items-center justify-center gap-2 text-center text-xs text-ink-500">
                    <x-icon name="cash" class="size-4" />
                    {{ __('You pay in cash when your order arrives.') }}
                </p>
            </aside>
        </form>

        {{-- The discount code box sits inside the checkout form, so its buttons submit these forms instead. --}}
        <form id="coupon-apply-form" method="POST" action="{{ route('cart.coupon.store') }}" class="hidden">
            @csrf
        </form>
        <form id="coupon-remove-form" method="POST" action="{{ route('cart.coupon.destroy') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</x-store.layout>
