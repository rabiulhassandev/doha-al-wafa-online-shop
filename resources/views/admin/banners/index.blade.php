<x-admin.layout title="Banners">
    <x-admin.page-header title="Home page banners" description="Slides at the top of the home page. Shoppers swipe through them in display order.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.banners.create')">
                <x-icon name="plus" class="size-4" />
                Add banner
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th class="w-32"><span class="sr-only">Image</span></x-admin.th>
            <x-admin.th>Title</x-admin.th>
            <x-admin.th>Link</x-admin.th>
            <x-admin.th>Schedule</x-admin.th>
            <x-admin.th>Order</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($banners as $banner)
            @php($isLive = $banner->isLive())
            <tr>
                <td class="px-4 py-3">
                    <img src="{{ $banner->imageUrl('image_path') }}" alt="" class="h-12 w-28 rounded-lg object-cover">
                </td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $banner->title_en ?: 'Image only' }}</p>
                    @if ($banner->title_ar)
                        <p class="text-ink-500" lang="ar">{{ $banner->title_ar }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-ink-700">{{ $banner->link ?: '—' }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">
                    @if ($banner->starts_at || $banner->ends_at)
                        {{ $banner->starts_at?->format('j M') ?? 'Now' }} → {{ $banner->ends_at?->format('j M Y') ?? 'No end' }}
                    @else
                        <span class="text-ink-500">Always</span>
                    @endif
                </td>
                <td class="px-4 py-3 tabular-nums">{{ $banner->sort_order }}</td>
                <td class="px-4 py-3">
                    <x-admin.badge :color="$isLive ? 'success' : 'neutral'">{{ $isLive ? 'Live' : 'Not showing' }}</x-admin.badge>
                </td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.banners.edit', $banner)"
                        :delete-url="route('admin.banners.destroy', $banner)"
                        delete-title="Delete this banner?"
                        delete-message="It is removed from the home page straight away."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="7" message="No banners yet. The home page shows a simple welcome message until you add one.">
                <x-admin.button :href="route('admin.banners.create')" size="sm">Add the first banner</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</x-admin.layout>
