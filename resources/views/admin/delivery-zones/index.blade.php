@inject('settings', 'App\Services\Settings')

<x-admin.layout title="Delivery areas">
    <x-admin.page-header title="Delivery areas" description="Shoppers choose their area at checkout and see its fee straight away.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.delivery-zones.create')">
                <x-icon name="plus" class="size-4" />
                Add area
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="text-sm text-ink-700">
        @if ($settings->get('delivery_free_over') !== null)
            Free delivery for orders from <strong>{{ \App\Support\Money::format((int) $settings->get('delivery_free_over')) }}</strong>, unless an area sets its own amount.
        @else
            There is no store-wide free delivery offer.
        @endif
        <a href="{{ route('admin.settings.edit') }}#delivery" class="font-medium text-maroon-700 hover:underline">Change in settings</a>
    </p>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Area</x-admin.th>
            <x-admin.th>Fee</x-admin.th>
            <x-admin.th>Free from</x-admin.th>
            <x-admin.th>Delivery time</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($deliveryZones as $deliveryZone)
            <tr>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $deliveryZone->name_en }}</p>
                    <p class="text-ink-500" lang="ar">{{ $deliveryZone->name_ar }}</p>
                </td>
                <td class="px-4 py-3 whitespace-nowrap tabular-nums">{{ \App\Support\Money::format($deliveryZone->fee) }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700 tabular-nums">
                    {{ $deliveryZone->free_over !== null ? \App\Support\Money::format($deliveryZone->free_over) : 'Store setting' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">
                    {{ $deliveryZone->eta_min_days === $deliveryZone->eta_max_days ? $deliveryZone->eta_min_days : "{$deliveryZone->eta_min_days}–{$deliveryZone->eta_max_days}" }} working days
                </td>
                <td class="px-4 py-3">
                    <x-admin.badge :color="$deliveryZone->is_active ? 'success' : 'neutral'">{{ $deliveryZone->is_active ? 'Delivering' : 'Paused' }}</x-admin.badge>
                </td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.delivery-zones.edit', $deliveryZone)"
                        :delete-url="route('admin.delivery-zones.destroy', $deliveryZone)"
                        :delete-title="'Delete “'.$deliveryZone->name_en.'”?'"
                        delete-message="Shoppers in this area will not be able to choose it. To stop deliveries for a while, pause it instead."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="6" message="No delivery areas yet. Shoppers cannot choose where to deliver until you add one.">
                <x-admin.button :href="route('admin.delivery-zones.create')" size="sm">Add the first area</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</x-admin.layout>
