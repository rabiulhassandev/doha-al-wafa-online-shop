<x-admin.layout :title="'Edit '.$coupon->code">
    <x-admin.page-header :title="'Edit '.$coupon->code" />

    <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.coupons.form', ['coupon' => $coupon])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.coupons.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
