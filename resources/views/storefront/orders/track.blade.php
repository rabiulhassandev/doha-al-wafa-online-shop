{{-- "Track your order": the order number and phone number open the order's page. --}}
@push('head')
    <meta name="robots" content="noindex">
@endpush

<x-store.layout :title="__('Track your order')">
    <div class="page-container py-10">
        <div class="mx-auto max-w-md">
            <h1 class="font-display text-3xl sm:text-4xl">{{ __('Track your order') }}</h1>
            <p class="mt-2 text-ink-700">{{ __('Enter your order number and the phone number you ordered with.') }}</p>

            <form method="POST" action="{{ route('track.lookup') }}" class="mt-6 grid gap-5 rounded-2xl bg-white p-5 sm:p-6">
                @csrf
                <x-store.text-field
                    name="number"
                    :label="__('Order number')"
                    :hint="__('It starts with DW-, for example DW-10001.')"
                    autocomplete="off"
                    maxlength="30"
                    required
                    dir="ltr"
                />

                <x-store.phone-field :label="__('Phone number')" required />

                <x-store.button type="submit" size="lg" class="w-full">{{ __('Track order') }}</x-store.button>
            </form>
        </div>
    </div>
</x-store.layout>
