<x-admin.layout title="Add page">
    <x-admin.page-header title="Add page" />

    <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-6">
        @csrf

        @include('admin.pages.form', ['page' => $page])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.pages.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add page</x-admin.button>
        </div>
    </form>
</x-admin.layout>
