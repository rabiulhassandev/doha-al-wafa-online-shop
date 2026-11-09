<x-admin.layout title="Stock history">
    <x-admin.page-header title="Stock history" description="Every change to stock, who made it and why." />

    <form method="GET" action="{{ route('admin.stock-movements.index') }}" class="grid gap-3 rounded-2xl border border-sand-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_14rem_auto] sm:items-end">
        <x-admin.input name="search" type="search" label="SKU" :value="$search" placeholder="DW0012-NAVY-56" />
        <x-admin.select name="reason" label="Reason" :options="['' => 'All reasons'] + $reasonOptions" :value="$reason?->value" />
        <div class="flex gap-2">
            <x-admin.button type="submit" variant="secondary">Filter</x-admin.button>
            @if ($search !== '' || $reason)
                <x-admin.button :href="route('admin.stock-movements.index')" variant="ghost">Clear</x-admin.button>
            @endif
        </div>
    </form>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>When</x-admin.th>
            <x-admin.th>Item</x-admin.th>
            <x-admin.th>Reason</x-admin.th>
            <x-admin.th class="text-end">Change</x-admin.th>
            <x-admin.th class="text-end">Stock after</x-admin.th>
            <x-admin.th>By</x-admin.th>
        </x-slot:head>

        @forelse ($movements as $movement)
            <tr>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">{{ $movement->created_at->format('j M Y, H:i') }}</td>
                <td class="px-4 py-3">
                    @if ($movement->productVariant)
                        <a href="{{ route('admin.inventory.adjust', $movement->productVariant) }}" class="font-medium hover:underline">
                            {{ $movement->productVariant->product->name_en }}
                        </a>
                        <p class="text-xs text-ink-500">{{ $movement->productVariant->productColor->nameEn() }} · {{ $movement->productVariant->size->name_en }}</p>
                    @else
                        <p class="text-ink-500">Removed item</p>
                    @endif
                    <p class="font-mono text-xs text-ink-500">{{ $movement->sku }}</p>
                </td>
                <td class="px-4 py-3">
                    {{ $movement->reason->label() }}
                    @if ($movement->note)
                        <p class="text-xs text-ink-500">{{ $movement->note }}</p>
                    @endif
                </td>
                <td @class(['px-4 py-3 text-end font-semibold tabular-nums', 'text-success-700' => $movement->change > 0, 'text-danger-700' => $movement->change < 0])>
                    {{ $movement->change > 0 ? '+' : '' }}{{ $movement->change }}
                </td>
                <td class="px-4 py-3 text-end tabular-nums">{{ $movement->stock_after }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">{{ $movement->user?->name ?? 'System' }}</td>
            </tr>
        @empty
            <x-admin.empty-row colspan="6" message="No stock changes yet." />
        @endforelse
    </x-admin.table>

    {{ $movements->links() }}
</x-admin.layout>
