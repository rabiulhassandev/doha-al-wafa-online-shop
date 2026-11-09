<x-admin.layout title="Colors">
    <x-admin.page-header title="Colors" description="The shared color palette. Products pick their colors from here, and shoppers filter by them.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.colors.create')">
                <x-icon name="plus" class="size-4" />
                Add color
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th class="w-16"><span class="sr-only">Swatch</span></x-admin.th>
            <x-admin.th>Name</x-admin.th>
            <x-admin.th>Code</x-admin.th>
            <x-admin.th>Order</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($colors as $color)
            <tr>
                <td class="px-4 py-3">@include('admin.colors.swatch', ['color' => $color])</td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $color->name_en }}</p>
                    <p class="text-ink-500" lang="ar">{{ $color->name_ar }}</p>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $color->hex }}</td>
                <td class="px-4 py-3 tabular-nums">{{ $color->sort_order }}</td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.colors.edit', $color)"
                        :delete-url="route('admin.colors.destroy', $color)"
                        :delete-title="'Delete “'.$color->name_en.'”?'"
                        delete-message="It will be removed from the palette."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="5" message="The palette is empty.">
                <x-admin.button :href="route('admin.colors.create')" size="sm">Add the first color</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>

    {{ $colors->links() }}
</x-admin.layout>
