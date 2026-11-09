{{--
    The phone menu and search overlay are rendered after this header in the layout, not inside it:
    "backdrop-blur" makes the header the containing block for fixed elements, which would squeeze
    full-screen overlays into the header's height.
--}}
<header x-data class="sticky top-0 z-40 border-b border-sand-200 bg-sand-50/95 backdrop-blur">
    <div class="page-container flex h-16 items-center gap-1 sm:gap-2">
        <button
            type="button"
            class="-ms-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100 lg:hidden"
            x-on:click="$store.mobileMenu.show()"
            aria-controls="mobile-menu"
            x-bind:aria-expanded="$store.mobileMenu.open.toString()"
        >
            <x-icon name="menu" class="size-6" />
            <span class="sr-only">{{ __('Open menu') }}</span>
        </button>

        <a href="{{ route('home') }}" class="me-auto lg:me-0">
            <x-store.logo />
        </a>

        <nav class="ms-10 me-auto hidden items-center gap-7 text-sm font-medium lg:flex" aria-label="{{ __('Main') }}">
            <a href="{{ route('shop') }}" @class(['hover:text-maroon-700', 'text-maroon-700' => request()->routeIs('shop')])>{{ __('Shop all') }}</a>
            @foreach ($navigation['categories']->take(5) as $category)
                <a href="{{ route('category.show', $category) }}" class="hover:text-maroon-700">{{ $category->translated('name') }}</a>
            @endforeach
            @foreach ($navigation['collections']->take(2) as $collection)
                <a href="{{ route('collection.show', $collection) }}" class="text-maroon-700 hover:underline">{{ $collection->translated('name') }}</a>
            @endforeach
        </nav>

        <x-store.language-switcher class="hidden sm:flex" />

        <button type="button" x-on:click="$dispatch('open-search')" class="inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100">
            <x-icon name="search" class="size-6" />
            <span class="sr-only">{{ __('Search') }}</span>
        </button>

        <a href="{{ route('wishlist') }}" class="relative inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100">
            <x-icon name="heart" class="size-6" />
            <span
                x-cloak
                x-show="$store.wishlist.ids.length > 0"
                x-text="$store.wishlist.ids.length"
                class="absolute end-1 top-1 inline-flex min-w-4.5 items-center justify-center rounded-full bg-maroon-700 px-1 text-[10px] leading-4.5 font-semibold text-white"
            ></span>
            <span class="sr-only">{{ __('Wishlist') }}</span>
        </a>

        <a
            href="{{ route('cart.show') }}"
            x-on:click.prevent="$store.cart.show()"
            class="relative -me-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100"
        >
            <x-icon name="bag" class="size-6" />
            <span
                x-show="$store.cart.count > 0"
                x-text="$store.cart.count"
                @if ($cartCount === 0) style="display: none" @endif
                class="absolute end-0.5 top-0.5 inline-flex min-w-4.5 items-center justify-center rounded-full bg-ink-900 px-1 text-[10px] leading-4.5 font-semibold text-white"
            >{{ $cartCount }}</span>
            <span class="sr-only">{{ __('Shopping bag') }}</span>
        </a>
    </div>
</header>
