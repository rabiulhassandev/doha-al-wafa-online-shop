<x-admin.layout title="Categories">
    <x-admin.page-header title="Categories" description="Shoppers browse the store by category. Sub-categories are listed under their parent.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.categories.create')">
                <x-icon name="plus" class="size-4" />
                Add category
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th class="w-16"><span class="sr-only">Image</span></x-admin.th>
            <x-admin.th>Name</x-admin.th>
            <x-admin.th>URL name</x-admin.th>
            <x-admin.th>Order</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($categories as $category)
            @include('admin.categories.row', ['category' => $category, 'isChild' => false])

            @foreach ($category->children as $child)
                @include('admin.categories.row', ['category' => $child, 'isChild' => true])
            @endforeach
        @empty
            <x-admin.empty-row colspan="6" message="No categories yet.">
                <x-admin.button :href="route('admin.categories.create')" size="sm">Add the first category</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</x-admin.layout>
