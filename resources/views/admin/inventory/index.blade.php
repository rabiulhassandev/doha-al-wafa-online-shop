<x-admin.layout title="Inventory">
    <x-admin.page-header title="Inventory" description="Stock of every color and size you sell, lowest first.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.stock-movements.index')" variant="secondary">Stock history</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="GET" action="{{ route('admin.inventory.index') }}" class="grid gap-3 rounded-2xl border border-sand-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_14rem_auto] sm:items-end">
        <x-admin.input name="search" type="search" label="Search" :value="$search" placeholder="Product name or SKU" />
        <x-admin.select
            name="stock"
            label="Stock"
            :options="['' => 'All stock levels'] + $stockFilterOptions"
            :value="$stockFilter"
            :hint="'Low stock means '.$lowStockThreshold.' or fewer left.'"
        />
        <div class="flex gap-2 sm:pb-6">
            <x-admin.button type="submit" variant="secondary">Filter</x-admin.button>
            @if ($search !== '' || $stockFilter)
                <x-admin.button :href="route('admin.inventory.index')" variant="ghost">Clear</x-admin.button>
            @endif
        </div>
    </form>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Product</x-admin.th>
            <x-admin.th>Color and size</x-admin.th>
            <x-admin.th>SKU</x-admin.th>
            <x-admin.th class="text-end">In stock</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($variants as $variant)
            <tr>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.products.stock.edit', $variant->product) }}" class="font-medium hover:underline">{{ $variant->product->name_en }}</a>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="flex items-center gap-2">
                        @include('admin.colors.swatch', ['color' => $variant->productColor->color, 'sizeClass' => 'size-5'])
                        {{ $variant->productColor->nameEn() }} · {{ $variant->size->name_en }}
                    </span>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $variant->sku }}</td>
                <td class="px-4 py-3 text-end">
                    @if ($variant->stock === 0)
                        <x-admin.badge color="danger">Out of stock</x-admin.badge>
                    @elseif ($variant->stock <= $lowStockThreshold)
                        <span class="font-semibold text-danger-700 tabular-nums">{{ $variant->stock }}</span>
                    @else
                        <span class="tabular-nums">{{ $variant->stock }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-end">
                    <a href="{{ route('admin.inventory.adjust', $variant) }}" class="font-medium whitespace-nowrap text-ink-900 hover:underline">Adjust</a>
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="5" :message="$search !== '' || $stockFilter ? 'Nothing matches these filters.' : 'No stock yet. Choose sizes on a product’s Sizes & stock tab.'" />
        @endforelse
    </x-admin.table>

    {{ $variants->links() }}
</x-admin.layout>
