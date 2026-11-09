<x-admin.layout title="Edit banner">
    <x-admin.page-header title="Edit banner" />

    <form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.banners.form', ['banner' => $banner])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.banners.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
