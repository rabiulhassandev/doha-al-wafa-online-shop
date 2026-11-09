@props(['productId'])

{{-- Saved in the shopper's browser. Works without an account. --}}
<button
    type="button"
    x-data
    x-on:click="$store.wishlist.toggle({{ (int) $productId }})"
    x-bind:aria-pressed="$store.wishlist.has({{ (int) $productId }}).toString()"
    x-bind:class="$store.wishlist.has({{ (int) $productId }}) ? 'text-maroon-700' : 'text-ink-700'"
    {{ $attributes->merge(['class' => 'inline-flex size-10 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur hover:bg-white']) }}
>
    <svg class="size-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round" aria-hidden="true" x-bind:fill="$store.wishlist.has({{ (int) $productId }}) ? 'currentColor' : 'none'" fill="none">
        <path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7.5 2.8C19.5 15.4 12 20 12 20z" />
    </svg>
    <span class="sr-only">{{ __('Save to wishlist') }}</span>
</button>
