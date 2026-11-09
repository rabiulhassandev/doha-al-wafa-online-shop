<x-admin.layout title="Collections">
    <x-admin.page-header title="Collections" description="Curated groups for campaigns, such as Eid or New arrivals. Schedule them to start and end automatically.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.collections.create')">
                <x-icon name="plus" class="size-4" />
                Add collection
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th class="w-24"><span class="sr-only">Banner</span></x-admin.th>
            <x-admin.th>Name</x-admin.th>
            <x-admin.th>Schedule</x-admin.th>
            <x-admin.th>Order</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($collections as $collection)
            @php($status = $collection->status())
            <tr>
                <td class="px-4 py-3">
                    @if ($collection->banner_path)
                        <img src="{{ $collection->imageUrl('banner_path') }}" alt="" class="h-10 w-16 rounded-lg object-cover">
                    @else
                        <span class="flex h-10 w-16 items-center justify-center rounded-lg bg-sand-100 text-ink-300">
                            <x-icon name="image" class="size-5" />
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $collection->name_en }}</p>
                    <p class="text-ink-500" lang="ar">{{ $collection->name_ar }}</p>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">
                    @if ($collection->starts_at || $collection->ends_at)
                        {{ $collection->starts_at?->format('j M Y, H:i') ?? 'Now' }}
                        <span class="text-ink-300">→</span>
                        {{ $collection->ends_at?->format('j M Y, H:i') ?? 'No end' }}
                    @else
                        <span class="text-ink-500">Always</span>
                    @endif
                </td>
                <td class="px-4 py-3 tabular-nums">{{ $collection->sort_order }}</td>
                <td class="px-4 py-3">
                    <x-admin.badge :color="$status->badgeColor()">{{ $status->label() }}</x-admin.badge>
                </td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.collections.edit', $collection)"
                        :delete-url="route('admin.collections.destroy', $collection)"
                        :delete-title="'Delete “'.$collection->name_en.'”?'"
                        delete-message="The collection page will stop working. Products stay in the store."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="6" message="No collections yet.">
                <x-admin.button :href="route('admin.collections.create')" size="sm">Add the first collection</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>

    {{ $collections->links() }}
</x-admin.layout>
