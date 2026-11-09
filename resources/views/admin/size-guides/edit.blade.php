<x-admin.layout :title="'Edit '.$sizeGuide->name">
    <x-admin.page-header :title="'Edit '.$sizeGuide->name" />

    <form method="POST" action="{{ route('admin.size-guides.update', $sizeGuide) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.size-guides.form', ['sizeGuide' => $sizeGuide])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.size-guides.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
