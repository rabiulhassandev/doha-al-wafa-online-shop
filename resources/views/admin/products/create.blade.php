<x-admin.layout title="Add product">
    <x-admin.page-header title="Add product" description="Start with the details. You add colors, photos and stock on the next screens." />

    <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-6">
        @csrf

        @include('admin.products.form')

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.products.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save and add photos</x-admin.button>
        </div>
    </form>
</x-admin.layout>
