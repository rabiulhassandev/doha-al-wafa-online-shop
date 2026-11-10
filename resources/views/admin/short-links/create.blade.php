<x-admin.layout title="Make a short link">
    <x-admin.page-header title="Make a short link" />

    <form method="POST" action="{{ route('admin.short-links.store') }}" class="space-y-6">
        @csrf

        @include('admin.short-links.form', ['shortLink' => $shortLink])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.short-links.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Make link</x-admin.button>
        </div>
    </form>
</x-admin.layout>
