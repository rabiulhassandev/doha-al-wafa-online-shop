<x-admin.layout title="Pages">
    <x-admin.page-header title="Pages" description="About, delivery, exchange and privacy pages, in English and Arabic. Shown pages are linked from the store footer.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.pages.create')">
                <x-icon name="plus" class="size-4" />
                Add page
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Page</x-admin.th>
            <x-admin.th>Web address</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($pages as $page)
            <tr>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $page->title_en }}</p>
                    <p class="text-ink-500" lang="ar">{{ $page->title_ar }}</p>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-ink-700">/pages/{{ $page->slug }}</td>
                <td class="px-4 py-3">
                    <x-admin.badge :color="$page->is_published ? 'success' : 'neutral'">{{ $page->is_published ? 'Shown' : 'Hidden' }}</x-admin.badge>
                </td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.pages.edit', $page)"
                        :delete-url="route('admin.pages.destroy', $page)"
                        :delete-title="'Delete “'.$page->title_en.'”?'"
                        delete-message="Links to this page will stop working. To take it down for a while, hide it instead."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="4" message="No pages yet.">
                <x-admin.button :href="route('admin.pages.create')" size="sm">Add the first page</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</x-admin.layout>
