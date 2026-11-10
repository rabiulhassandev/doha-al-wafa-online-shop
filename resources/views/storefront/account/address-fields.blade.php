{{--
    Address fields for the address book. Expects $address (an App\Models\Address, new or saved) and $deliveryZones.
    Field ids get a prefix so several forms can be on one page.
--}}
@php
    $prefix = $address->exists ? "address-{$address->id}-" : 'new-address-';
    $fieldClasses = 'mt-1.5 block h-12 w-full rounded-xl border border-sand-300 bg-white px-4 text-base focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none';
    $isOldForm = (string) old('address_id') === (string) ($address->id ?? 'new');
    $value = fn (string $field): ?string => $isOldForm ? old($field, $address->{$field}) : $address->{$field};
    $error = fn (string $field): ?string => $isOldForm ? $errors->first($field) : null;
@endphp

<input type="hidden" name="address_id" value="{{ $address->id ?? 'new' }}">

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="{{ $prefix }}label" class="block text-sm font-medium">{{ __('Name for this address') }} <span class="font-normal text-ink-500">{{ __('(optional)') }}</span></label>
        <input id="{{ $prefix }}label" name="label" value="{{ $value('label') }}" maxlength="40" placeholder="{{ __('Home, Work…') }}" class="{{ $fieldClasses }}">
    </div>
    <div>
        <label for="{{ $prefix }}delivery_zone_id" class="block text-sm font-medium">{{ __('Delivery area') }}</label>
        <select id="{{ $prefix }}delivery_zone_id" name="delivery_zone_id" required class="{{ $fieldClasses }}">
            <option value="" disabled @selected(blank($value('delivery_zone_id')))>{{ __('Choose your area') }}</option>
            @foreach ($deliveryZones as $zone)
                <option value="{{ $zone->id }}" @selected((string) $value('delivery_zone_id') === (string) $zone->id)>{{ $zone->translated('name') }}</option>
            @endforeach
        </select>
        @if ($error('delivery_zone_id'))
            <p class="mt-1.5 text-xs font-medium text-danger-700">{{ $error('delivery_zone_id') }}</p>
        @endif
    </div>
</div>

<div class="grid grid-cols-3 gap-3">
    @foreach (['zone_number' => __('Zone'), 'street_number' => __('Street'), 'building_number' => __('Building')] as $field => $label)
        <div>
            <label for="{{ $prefix.$field }}" class="block text-sm font-medium">{{ $label }}</label>
            <input id="{{ $prefix.$field }}" name="{{ $field }}" value="{{ $value($field) }}" inputmode="numeric" maxlength="4" required class="{{ $fieldClasses }}">
            @if ($error($field))
                <p class="mt-1.5 text-xs font-medium text-danger-700">{{ $error($field) }}</p>
            @endif
        </div>
    @endforeach
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="{{ $prefix }}unit" class="block text-sm font-medium">{{ __('Villa, apartment or floor') }} <span class="font-normal text-ink-500">{{ __('(optional)') }}</span></label>
        <input id="{{ $prefix }}unit" name="unit" value="{{ $value('unit') }}" maxlength="60" class="{{ $fieldClasses }}">
    </div>
    <div>
        <label for="{{ $prefix }}landmark" class="block text-sm font-medium">{{ __('Landmark or directions') }} <span class="font-normal text-ink-500">{{ __('(optional)') }}</span></label>
        <input id="{{ $prefix }}landmark" name="landmark" value="{{ $value('landmark') }}" maxlength="200" class="{{ $fieldClasses }}">
    </div>
</div>

@unless ($address->is_default)
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_default" value="1" class="size-4 accent-maroon-700">
        {{ __('Use this address first at checkout') }}
    </label>
@endunless
