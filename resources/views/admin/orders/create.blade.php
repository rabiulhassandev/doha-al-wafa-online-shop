@php
    use App\Support\Money;

    $zoneOptions = ['' => 'Choose an area'] + $deliveryZones
        ->mapWithKeys(fn ($zone) => [$zone->id => $zone->name_en.' · '.($zone->fee === 0 ? 'Free' : Money::format($zone->fee))])
        ->all();
    $languageOptions = collect(config('shop.locales'))->map(fn (array $locale): string => $locale['label'])->all();
@endphp

<x-admin.layout title="Enter an order">
    <x-admin.page-header
        title="Enter an order"
        description="For orders taken on WhatsApp, Instagram or the phone. The order starts confirmed and takes the stock straight away."
    />

    @if ($errors->has('lines') || $errors->has('lines.*'))
        <div class="rounded-xl border border-danger-700/20 bg-danger-50 px-4 py-3 text-sm text-danger-700" role="alert">
            {{ $errors->first('lines') ?: $errors->first('lines.*') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.orders.store') }}" class="space-y-6">
        @csrf

        <x-admin.card title="Items" description="Prices come from the catalog. Change the delivery fee below if you agreed a different one.">
            <x-admin.variant-picker name="lines" :lines="$lines" />
        </x-admin.card>

        <x-admin.card title="Customer">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.select name="channel" label="Order taken on" :options="$channels" required />
                <x-admin.select name="locale" label="Customer’s language" :options="$languageOptions" :value="config('shop.default_locale')" hint="Used for the invoice and any email to the customer." />
                <x-admin.input name="name" label="Full name" maxlength="120" required />
                <x-admin.input name="phone" type="tel" label="Mobile number" prefix="+974" inputmode="tel" placeholder="5555 1234" required />
                <div class="sm:col-span-2">
                    <x-admin.input name="email" type="email" label="Email (optional)" hint="The customer gets an order confirmation email when you add one." />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Delivery">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-admin.select name="delivery_zone_id" label="Delivery area" :options="$zoneOptions" required />
                </div>
                <div class="grid grid-cols-3 gap-3 sm:col-span-2">
                    <x-admin.input name="zone_number" label="Zone" inputmode="numeric" maxlength="4" required />
                    <x-admin.input name="street_number" label="Street" inputmode="numeric" maxlength="4" required />
                    <x-admin.input name="building_number" label="Building" inputmode="numeric" maxlength="4" required />
                </div>
                <x-admin.input name="unit" label="Villa, apartment or floor (optional)" maxlength="60" />
                <x-admin.input name="landmark" label="Landmark or directions (optional)" maxlength="200" />
                <x-admin.input name="preferred_delivery_date" type="date" label="Preferred delivery date (optional)" :min="today()->toDateString()" />
                <x-admin.input
                    name="delivery_fee"
                    label="Delivery fee (optional)"
                    prefix="QAR"
                    inputmode="decimal"
                    hint="Leave empty to charge the area’s usual fee, including free delivery."
                />
            </div>
        </x-admin.card>

        <x-admin.card title="Notes">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.textarea name="customer_note" label="Note from the customer (optional)" maxlength="500" hint="Printed on the packing slip." />
                <x-admin.textarea name="admin_note" label="Team note (optional)" maxlength="2000" hint="Only the team sees this." />
            </div>
        </x-admin.card>

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.orders.index')" variant="ghost">Cancel</x-admin.button>
            <x-admin.button type="submit">Create order</x-admin.button>
        </div>
    </form>
</x-admin.layout>
