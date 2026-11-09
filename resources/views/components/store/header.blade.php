<header
    x-data="{ menuOpen: false }"
    x-effect="document.body.classList.toggle('overflow-hidden', menuOpen)"
    class="sticky top-0 z-40 border-b border-sand-200 bg-sand-50/95 backdrop-blur"
>
    <div class="page-container flex h-16 items-center gap-1 sm:gap-2">
        <button
            type="button"
            class="-ms-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100 lg:hidden"
            x-on:click="menuOpen = true"
            aria-controls="mobile-menu"
            x-bind:aria-expanded="menuOpen.toString()"
        >
            <x-icon name="menu" class="size-6" />
            <span class="sr-only">{{ __('Open menu') }}</span>
        </button>

        <a href="{{ route('home') }}" class="me-auto lg:me-0">
            <x-store.logo />
        </a>

        <nav class="ms-12 me-auto hidden items-center gap-8 text-sm font-medium lg:flex" aria-label="{{ __('Main') }}">
            <a href="{{ route('home') }}" @class(['hover:text-maroon-700', 'text-maroon-700' => request()->routeIs('home')])>
                {{ __('Home') }}
            </a>
        </nav>

        <x-store.language-switcher class="hidden sm:flex" />

        {{-- Search and bag go live in Phases 3 and 4. --}}
        <button type="button" class="inline-flex size-11 items-center justify-center rounded-full text-ink-300" disabled>
            <x-icon name="search" class="size-6" />
            <span class="sr-only">{{ __('Search') }}</span>
        </button>

        <button type="button" class="-me-2 inline-flex size-11 items-center justify-center rounded-full text-ink-300" disabled>
            <x-icon name="bag" class="size-6" />
            <span class="sr-only">{{ __('Shopping bag') }}</span>
        </button>
    </div>

    <x-store.mobile-menu />
</header>
