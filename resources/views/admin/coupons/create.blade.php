<x-admin.layout title="Add discount code">
    <x-admin.page-header title="Add discount code" />

    <form method="POST" action="{{ route('admin.coupons.store') }}" class="space-y-6">
        @csrf

        @include('admin.coupons.form', ['coupon' => $coupon])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.coupons.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add code</x-admin.button>
        </div>
    </form>
</x-admin.layout>
