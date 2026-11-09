<x-admin.layout :title="'Edit '.$category->name_en">
    <x-admin.page-header :title="'Edit '.$category->name_en" />

    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.categories.form', ['category' => $category, 'parentOptions' => $parentOptions])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.categories.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
