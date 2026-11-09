<x-admin.layout :title="'Edit size '.$size->name_en">
    <x-admin.page-header :title="'Edit size '.$size->name_en" />

    <form method="POST" action="{{ route('admin.sizes.update', $size) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.sizes.form', ['size' => $size])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.sizes.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
