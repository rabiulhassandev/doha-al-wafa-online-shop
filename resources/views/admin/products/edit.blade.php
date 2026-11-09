<x-admin.layout :title="$product->name_en">
    <x-admin.product-tabs :product="$product" current="details" />

    <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.products.form')

        <div class="sticky bottom-0 -mx-4 flex justify-end gap-3 border-t border-sand-200 bg-sand-100/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0">
            <x-admin.button type="submit">Save details</x-admin.button>
        </div>
    </form>
</x-admin.layout>
