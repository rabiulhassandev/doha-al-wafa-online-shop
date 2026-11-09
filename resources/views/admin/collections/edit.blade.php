<x-admin.layout :title="'Edit '.$collection->name_en">
    <x-admin.page-header :title="'Edit '.$collection->name_en" />

    <form method="POST" action="{{ route('admin.collections.update', $collection) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.collections.form', ['collection' => $collection])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.collections.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
