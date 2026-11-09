<x-admin.layout :title="'Edit '.$color->name_en">
    <x-admin.page-header :title="'Edit '.$color->name_en" />

    <form method="POST" action="{{ route('admin.colors.update', $color) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.colors.form', ['color' => $color])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.colors.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
