<x-admin.layout title="Add collection">
    <x-admin.page-header title="Add collection" />

    <form method="POST" action="{{ route('admin.collections.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.collections.form', ['collection' => $collection])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.collections.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add collection</x-admin.button>
        </div>
    </form>
</x-admin.layout>
