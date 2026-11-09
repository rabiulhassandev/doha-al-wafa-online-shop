<x-admin.layout title="Add delivery area">
    <x-admin.page-header title="Add delivery area" />

    <form method="POST" action="{{ route('admin.delivery-zones.store') }}" class="space-y-6">
        @csrf

        @include('admin.delivery-zones.form', ['deliveryZone' => $deliveryZone])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.delivery-zones.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add area</x-admin.button>
        </div>
    </form>
</x-admin.layout>
