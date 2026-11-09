<x-admin.layout title="Add size">
    <x-admin.page-header title="Add size" />

    <form method="POST" action="{{ route('admin.sizes.store') }}" class="space-y-6">
        @csrf

        @include('admin.sizes.form', ['size' => $size])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.sizes.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add size</x-admin.button>
        </div>
    </form>
</x-admin.layout>
