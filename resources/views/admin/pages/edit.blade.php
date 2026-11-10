<x-admin.layout :title="'Edit '.$page->title_en">
    <x-admin.page-header :title="'Edit '.$page->title_en">
        @if ($page->is_published)
            <x-slot:actions>
                <x-admin.button :href="route('pages.show', ['locale' => 'en', 'page' => $page])" variant="secondary" target="_blank">
                    <x-icon name="external" class="size-4" />
                    View in the store
                </x-admin.button>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.pages.form', ['page' => $page])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.pages.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
