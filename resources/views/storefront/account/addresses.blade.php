{{-- Saved delivery addresses. Expects $addresses and $deliveryZones. --}}
<x-store.account-layout :title="__('Addresses')">
    <p class="text-sm text-ink-700">{{ __('Checkout fills in your first address for you. You can pick another one there.') }}</p>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        @foreach ($addresses as $address)
            <details class="group rounded-2xl bg-white" @if ((string) old('address_id') === (string) $address->id) open @endif>
                <summary class="flex cursor-pointer list-none items-start justify-between gap-3 p-5 [&::-webkit-details-marker]:hidden">
                    <span>
                        <span class="flex items-center gap-2 font-semibold">
                            {{ $address->label ?: __('Address') }}
                            @if ($address->is_default)
                                <span class="rounded-full bg-maroon-50 px-2.5 py-0.5 text-xs font-medium text-maroon-700">{{ __('Used first') }}</span>
                            @endif
                        </span>
                        <span class="mt-1 block text-sm text-ink-700">
                            {{ $address->deliveryZone?->translated('name') ?? __('Area no longer delivered to') }}<br>
                            {{ $address->plateAddress() }}
                            @if ($address->unit)
                                <br>{{ $address->unit }}
                            @endif
                        </span>
                    </span>
                    <span class="text-sm font-medium text-maroon-700 underline underline-offset-4">{{ __('Edit') }}</span>
                </summary>

                <div class="space-y-4 border-t border-sand-200 p-5">
                    <form method="POST" action="{{ route('account.addresses.update', $address) }}" novalidate class="space-y-4">
                        @csrf
                        @method('PUT')
                        @include('storefront.account.address-fields', ['address' => $address, 'deliveryZones' => $deliveryZones])
                        <x-store.button type="submit">{{ __('Save address') }}</x-store.button>
                    </form>
                    <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-danger-700 underline underline-offset-4">{{ __('Delete this address') }}</button>
                    </form>
                </div>
            </details>
        @endforeach
    </div>

    <section class="mt-6 rounded-2xl bg-white p-5 sm:p-6" aria-labelledby="new-address-title">
        <h2 id="new-address-title" class="text-base font-semibold">{{ $addresses->isEmpty() ? __('Add your address') : __('Add another address') }}</h2>
        <form method="POST" action="{{ route('account.addresses.store') }}" novalidate class="mt-4 space-y-4">
            @csrf
            @include('storefront.account.address-fields', ['address' => new \App\Models\Address, 'deliveryZones' => $deliveryZones])
            <x-store.button type="submit">{{ __('Save address') }}</x-store.button>
        </form>
    </section>
</x-store.account-layout>
