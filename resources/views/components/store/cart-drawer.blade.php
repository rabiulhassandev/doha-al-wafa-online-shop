{{--
    Slide-in bag. Its contents are rendered by the server (storefront.cart.drawer) and loaded
    through Alpine.store('cart'), which also handles adding from product pages.
--}}
<div
    id="cart-drawer"
    x-data
    data-count="{{ $cartCount }}"
    data-drawer-url="{{ route('cart.drawer') }}"
    data-add-url="{{ route('cart.items.store') }}"
    x-cloak
    x-show="$store.cart.open"
    x-effect="document.body.classList.toggle('overflow-hidden', $store.cart.open || $store.mobileMenu.open)"
    x-on:keydown.escape.window="$store.cart.hide()"
    class="fixed inset-0 z-50"
    role="dialog"
    aria-modal="true"
    aria-labelledby="cart-drawer-title"
>
    <div x-show="$store.cart.open" x-transition.opacity class="absolute inset-0 bg-ink-900/40" x-on:click="$store.cart.hide()"></div>

    <div
        x-show="$store.cart.open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="ltr:translate-x-full rtl:-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="ltr:translate-x-full rtl:-translate-x-full"
        class="absolute inset-y-0 end-0 flex w-full max-w-md flex-col bg-sand-50 shadow-xl"
    >
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-sand-200 px-5">
            <h2 id="cart-drawer-title" class="text-base font-semibold">
                {{ __('Your bag') }}
                <span x-show="$store.cart.count > 0" class="font-normal text-ink-500">(<span x-text="$store.cart.count"></span>)</span>
            </h2>
            <button type="button" x-on:click="$store.cart.hide()" class="-me-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100">
                <x-icon name="close" class="size-6" />
                <span class="sr-only">{{ __('Close bag') }}</span>
            </button>
        </div>

        <p x-show="$store.cart.loading && ! $store.cart.html" class="p-5 text-sm text-ink-500">{{ __('Loading…') }}</p>

        {{-- Forms inside submit in the background and refresh this panel. --}}
        <div
            class="flex min-h-0 flex-1 flex-col"
            x-html="$store.cart.html"
            x-on:submit.prevent="$store.cart.submit($event.target)"
        ></div>
    </div>
</div>
