<x-admin.layout title="Size guides">
    <x-admin.page-header title="Size guides" description="Measurement tables shoppers open from a product page. Each product can use one guide.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.size-guides.create')">
                <x-icon name="plus" class="size-4" />
                Add size guide
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Name</x-admin.th>
            <x-admin.th>Measurements</x-admin.th>
            <x-admin.th>Sizes</x-admin.th>
            <x-admin.th>Last updated</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($sizeGuides as $sizeGuide)
            <tr>
                <td class="px-4 py-3 font-medium">{{ $sizeGuide->name }}</td>
                <td class="px-4 py-3 text-ink-700">{{ collect($sizeGuide->columns)->pluck('en')->implode(', ') }}</td>
                <td class="px-4 py-3 text-ink-700">{{ collect($sizeGuide->rows)->pluck('size')->implode(', ') }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-500">{{ $sizeGuide->updated_at->format('j M Y') }}</td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.size-guides.edit', $sizeGuide)"
                        :delete-url="route('admin.size-guides.destroy', $sizeGuide)"
                        :delete-title="'Delete “'.$sizeGuide->name.'”?'"
                        delete-message="Products using it will no longer show a size guide."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="5" message="No size guides yet.">
                <x-admin.button :href="route('admin.size-guides.create')" size="sm">Add the first size guide</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>

    {{ $sizeGuides->links() }}
</x-admin.layout>
