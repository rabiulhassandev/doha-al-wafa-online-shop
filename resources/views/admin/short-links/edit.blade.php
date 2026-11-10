<x-admin.layout :title="'Edit '.$shortLink->label">
    <x-admin.page-header :title="'Edit '.$shortLink->label" :description="$shortLink->shortUrl()" />

    <form method="POST" action="{{ route('admin.short-links.update', $shortLink) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.short-links.form', ['shortLink' => $shortLink])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.short-links.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
