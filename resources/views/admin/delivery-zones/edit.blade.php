<x-admin.layout :title="'Edit '.$deliveryZone->name_en">
    <x-admin.page-header :title="'Edit '.$deliveryZone->name_en" />

    <form method="POST" action="{{ route('admin.delivery-zones.update', $deliveryZone) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.delivery-zones.form', ['deliveryZone' => $deliveryZone])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.delivery-zones.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
