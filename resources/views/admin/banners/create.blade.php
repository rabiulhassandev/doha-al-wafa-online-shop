<x-admin.layout title="Add banner">
    <x-admin.page-header title="Add banner" />

    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.banners.form', ['banner' => $banner])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.banners.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add banner</x-admin.button>
        </div>
    </form>
</x-admin.layout>
