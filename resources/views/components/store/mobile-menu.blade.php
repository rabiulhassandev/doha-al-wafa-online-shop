{{-- Rendered inside the header, which owns the "menuOpen" state. --}}
<div
    id="mobile-menu"
    x-cloak
    x-show="menuOpen"
    x-on:keydown.escape.window="menuOpen = false"
    class="fixed inset-0 z-50 lg:hidden"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Menu') }}"
>
    <div x-show="menuOpen" x-transition.opacity class="absolute inset-0 bg-ink-900/40" x-on:click="menuOpen = false"></div>

    <div
        x-show="menuOpen"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="ltr:-translate-x-full rtl:translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="ltr:-translate-x-full rtl:translate-x-full"
        x-init="$watch('menuOpen', (open) => open && $nextTick(() => $refs.closeMenu.focus()))"
        class="absolute inset-y-0 start-0 flex w-[85%] max-w-sm flex-col bg-sand-50 shadow-xl"
    >
        <div class="flex h-16 items-center justify-between border-b border-sand-200 px-4">
            <x-store.logo />

            <button
                type="button"
                x-ref="closeMenu"
                class="-me-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100"
                x-on:click="menuOpen = false"
            >
                <x-icon name="close" class="size-6" />
                <span class="sr-only">{{ __('Close menu') }}</span>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-4" aria-label="{{ __('Main') }}">
            <a href="{{ route('home') }}" class="flex h-12 items-center justify-between rounded-xl px-3 text-base font-medium hover:bg-sand-100">
                {{ __('Home') }}
                <x-icon name="chevron-end" class="size-4 text-ink-300" />
            </a>
        </nav>

        <div class="border-t border-sand-200 p-4">
            <x-store.language-switcher />
        </div>
    </div>
</div>
