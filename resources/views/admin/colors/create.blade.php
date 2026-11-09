<x-admin.layout title="Add color">
    <x-admin.page-header title="Add color" />

    <form method="POST" action="{{ route('admin.colors.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.colors.form', ['color' => $color])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.colors.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add color</x-admin.button>
        </div>
    </form>
</x-admin.layout>
