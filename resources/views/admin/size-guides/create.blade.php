<x-admin.layout title="Add size guide">
    <x-admin.page-header title="Add size guide" />

    <form method="POST" action="{{ route('admin.size-guides.store') }}" class="space-y-6">
        @csrf

        @include('admin.size-guides.form', ['sizeGuide' => $sizeGuide])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.size-guides.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add size guide</x-admin.button>
        </div>
    </form>
</x-admin.layout>
