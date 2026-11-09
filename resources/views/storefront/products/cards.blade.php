{{-- Product cards only, loaded into pages such as the wishlist. Expects $products. --}}
@foreach ($products as $product)
    <x-store.product-card :product="$product" />
@endforeach
