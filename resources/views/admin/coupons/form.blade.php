{{-- Shared discount code fields. Expects $coupon. --}}
@php
    use App\Enums\CouponType;
    use App\Support\Money;

    $type = old('type', $coupon->type?->value ?? CouponType::Percent->value);
@endphp

<x-admin.card title="Code">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="code" label="Code" :value="$coupon->code" required maxlength="30" placeholder="EID10" hint="Shoppers type it, or open a link with it. Letters, digits and dashes." class="uppercase" />
        <x-admin.input name="description" label="Note for the team (optional)" :value="$coupon->description" maxlength="200" placeholder="Influencer Sara, Eid campaign" />
    </div>
</x-admin.card>

<x-admin.card title="Discount">
    <div class="grid gap-5 sm:grid-cols-2" x-data="{ type: @js($type) }">
        <x-admin.select name="type" label="Type" :options="CouponType::options()" :value="$type" x-model="type" required />
        <div x-show="type === 'percent'">
            <x-admin.input name="percent" type="number" label="Percent off" :value="$coupon->type === CouponType::Percent ? $coupon->value : null" min="1" max="100" prefix="%" />
        </div>
        <div x-show="type === 'fixed'" x-cloak>
            <x-admin.input name="amount" label="Amount off" :value="$coupon->type === CouponType::Fixed ? Money::toInput($coupon->value) : null" prefix="QAR" inputmode="decimal" />
        </div>
        <p x-show="type === 'free_delivery'" x-cloak class="text-sm text-ink-500 sm:pt-7">Takes the delivery fee off the order.</p>
        <x-admin.input name="min_subtotal" label="Minimum spend (optional)" :value="Money::toInput($coupon->min_subtotal)" prefix="QAR" inputmode="decimal" hint="Before delivery. Shoppers see how much more they need to spend." />
    </div>
</x-admin.card>

<x-admin.card title="When and how often" description="Leave a field empty for no limit.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="starts_at" type="datetime-local" label="Starts" :value="$coupon->starts_at?->format('Y-m-d\TH:i')" />
        <x-admin.input name="ends_at" type="datetime-local" label="Ends" :value="$coupon->ends_at?->format('Y-m-d\TH:i')" />
        <x-admin.input name="max_uses" type="number" label="Total uses" :value="$coupon->max_uses" min="1" hint="Uses on cancelled orders do not count." />
        <x-admin.input name="max_uses_per_phone" type="number" label="Uses per customer" :value="$coupon->max_uses_per_phone" min="1" hint="Counted by mobile number." />
        <x-admin.toggle name="first_order_only" label="First order only" :checked="$coupon->first_order_only" hint="For mobile numbers that never ordered before." />
        <x-admin.toggle name="is_active" label="Code can be used" :checked="$coupon->exists ? $coupon->is_active : true" />
    </div>
</x-admin.card>
