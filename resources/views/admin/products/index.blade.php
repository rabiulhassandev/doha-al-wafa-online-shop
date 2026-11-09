<x-admin.layout title="Products">
    <x-admin.page-header title="Products" description="Everything you sell. Open a product to manage its colors, photos and stock.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.products.create')">
                <x-icon name="plus" class="size-4" />
                Add product
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="GET" action="{{ route('admin.products.index') }}" class="grid gap-3 rounded-2xl border border-sand-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_12rem_12rem_auto] sm:items-end">
        <x-admin.input name="search" type="search" label="Search" :value="$search" placeholder="Name or product code" />
        <x-admin.select name="category" label="Category" :options="['' => 'All categories'] + $categoryOptions" :value="$categoryId" />
        <x-admin.select name="status" label="Status" :options="['' => 'All statuses'] + \App\Enums\ProductStatus::options()" :value="$status?->value" />
        <div class="flex gap-2">
            <x-admin.button type="submit" variant="secondary">Filter</x-admin.button>
            @if ($search !== '' || $status || $categoryId)
                <x-admin.button :href="route('admin.products.index')" variant="ghost">Clear</x-admin.button>
            @endif
        </div>
    </form>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th class="w-16"><span class="sr-only">Photo</span></x-admin.th>
            <x-admin.th>Product</x-admin.th>
            <x-admin.th>Colors</x-admin.th>
            <x-admin.th>Price</x-admin.th>
            <x-admin.th>Stock</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($products as $product)
            @php
                $coverColor = $product->colors->firstWhere('is_default', true) ?? $product->colors->first();
                $coverImage = $coverColor?->images->first();
            @endphp
            <tr>
                <td class="px-4 py-3">
                    @if ($coverImage)
                        <img src="{{ $coverImage->url(400) }}" alt="" class="h-15 w-12 rounded-lg object-cover" loading="lazy">
                    @else
                        <span class="flex h-15 w-12 items-center justify-center rounded-lg bg-sand-100 text-ink-300">
                            <x-icon name="image" class="size-5" />
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.products.edit', $product) }}" class="font-medium hover:underline">{{ $product->name_en }}</a>
                    <p class="text-ink-500" lang="ar">{{ $product->name_ar }}</p>
                    <p class="mt-0.5 text-xs text-ink-500">{{ $product->code }} · {{ $product->category->name_en }}</p>
                </td>
                <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1">
                        @foreach ($product->colors as $productColor)
                            <span title="{{ $productColor->nameEn() }}" @class(['opacity-40' => ! $productColor->is_active])>
                                @include('admin.colors.swatch', ['color' => $productColor->color, 'sizeClass' => 'size-5'])
                            </span>
                        @endforeach
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap tabular-nums">
                    {{ \App\Support\Money::format($product->price) }}
                    @if ($product->compare_at_price)
                        <span class="ms-1 text-xs text-ink-500 line-through">{{ \App\Support\Money::format($product->compare_at_price) }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 tabular-nums">
                    <a href="{{ route('admin.products.stock.edit', $product) }}" @class(['hover:underline', 'font-medium text-danger-700' => (int) $product->stock_total === 0])>
                        {{ (int) $product->stock_total }}
                    </a>
                </td>
                <td class="px-4 py-3">
                    <x-admin.badge :color="$product->status->badgeColor()">{{ $product->status->label() }}</x-admin.badge>
                </td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.products.edit', $product)"
                        :delete-url="route('admin.products.destroy', $product)"
                        :delete-title="'Delete “'.$product->name_en.'”?'"
                        delete-message="Its colors, photos, sizes and stock are deleted too. To hide a product but keep it, set it to Archived instead."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="7" :message="$search !== '' || $status || $categoryId ? 'No products match these filters.' : 'No products yet.'">
                <x-admin.button :href="route('admin.products.create')" size="sm">Add a product</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>

    {{ $products->links() }}
</x-admin.layout>
