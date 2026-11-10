@php
    use App\Support\Money;
@endphp

<x-admin.layout title="Discount codes">
    <x-admin.page-header title="Discount codes" description="Shoppers type a code in the bag or at checkout, or open a link that applies it for them.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.coupons.create')">
                <x-icon name="plus" class="size-4" />
                Add code
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Code</x-admin.th>
            <x-admin.th>Discount</x-admin.th>
            <x-admin.th class="text-end">Uses</x-admin.th>
            <x-admin.th class="text-end">Discount given</x-admin.th>
            <x-admin.th class="text-end">Sales</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($coupons as $coupon)
            @php
                $problem = $coupon->availabilityProblem();
            @endphp
            <tr>
                <td class="px-4 py-3">
                    <p class="font-mono font-semibold">{{ $coupon->code }}</p>
                    @if ($coupon->description)
                        <p class="text-xs text-ink-500">{{ $coupon->description }}</p>
                    @endif
                    <p class="mt-1 max-w-xs truncate text-xs text-ink-500" title="Share this link to apply the code">
                        Link: <span class="font-mono select-all">{{ route('home', ['locale' => 'ar', 'coupon' => $coupon->code]) }}</span>
                    </p>
                </td>
                <td class="px-4 py-3 text-ink-700">
                    {{ $coupon->benefit() }}
                    <p class="text-xs text-ink-500">
                        @if ($coupon->min_subtotal)
                            From {{ Money::format($coupon->min_subtotal) }}.
                        @endif
                        @if ($coupon->first_order_only)
                            First orders only.
                        @endif
                        @if ($coupon->ends_at)
                            Until {{ $coupon->ends_at->format('j M Y, H:i') }}.
                        @endif
                    </p>
                </td>
                <td class="px-4 py-3 text-end tabular-nums">{{ $coupon->uses_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</td>
                <td class="px-4 py-3 text-end whitespace-nowrap tabular-nums">{{ Money::format((int) $coupon->discount_given) }}</td>
                <td class="px-4 py-3 text-end whitespace-nowrap tabular-nums">{{ Money::format((int) $coupon->sales) }}</td>
                <td class="px-4 py-3">
                    @if ($problem === null)
                        <x-admin.badge color="success">Working</x-admin.badge>
                    @else
                        <x-admin.badge>{{ $coupon->is_active ? 'Not working now' : 'Off' }}</x-admin.badge>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.coupons.edit', $coupon)"
                        :delete-url="$coupon->uses_count === 0 ? route('admin.coupons.destroy', $coupon) : null"
                        :delete-title="'Delete '.$coupon->code.'?'"
                        delete-message="Shoppers will no longer be able to use it."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="7" message="No discount codes yet.">
                <x-admin.button :href="route('admin.coupons.create')" size="sm">Add the first code</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</x-admin.layout>
