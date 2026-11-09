<x-admin.layout title="Add category">
    <x-admin.page-header title="Add category" />

    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.categories.form', ['category' => $category, 'parentOptions' => $parentOptions])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.categories.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add category</x-admin.button>
        </div>
    </form>
</x-admin.layout>
