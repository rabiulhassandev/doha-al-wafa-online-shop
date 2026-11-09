<x-admin.layout title="Sizes">
    <x-admin.page-header title="Sizes" description="Every size a product can come in. Order them from smallest to largest.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.sizes.create')">
                <x-icon name="plus" class="size-4" />
                Add size
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Size</x-admin.th>
            <x-admin.th>Arabic name</x-admin.th>
            <x-admin.th>Type</x-admin.th>
            <x-admin.th>Order</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($sizes as $size)
            <tr>
                <td class="px-4 py-3 font-medium">{{ $size->name_en }}</td>
                <td class="px-4 py-3 text-ink-700" lang="ar">{{ $size->name_ar ?? '—' }}</td>
                <td class="px-4 py-3 text-ink-700">{{ $size->group->label() }}</td>
                <td class="px-4 py-3 tabular-nums">{{ $size->sort_order }}</td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.sizes.edit', $size)"
                        :delete-url="route('admin.sizes.destroy', $size)"
                        :delete-title="'Delete size “'.$size->name_en.'”?'"
                        delete-message="It will no longer be available for products."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="5" message="No sizes yet.">
                <x-admin.button :href="route('admin.sizes.create')" size="sm">Add the first size</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>

    {{ $sizes->links() }}
</x-admin.layout>
