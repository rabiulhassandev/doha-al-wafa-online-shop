{{-- Opened by the header's menu button through Alpine.store('mobileMenu'). --}}
<div
    id="mobile-menu"
    x-data
    x-cloak
    x-show="$store.mobileMenu.open"
    x-effect="document.body.classList.toggle('overflow-hidden', $store.mobileMenu.open)"
    x-on:keydown.escape.window="$store.mobileMenu.hide()"
    class="fixed inset-0 z-50 lg:hidden"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Menu') }}"
>
    <div x-show="$store.mobileMenu.open" x-transition.opacity class="absolute inset-0 bg-ink-900/40" x-on:click="$store.mobileMenu.hide()"></div>

    <div
        x-show="$store.mobileMenu.open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="ltr:-translate-x-full rtl:translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="ltr:-translate-x-full rtl:translate-x-full"
        x-init="$watch('$store.mobileMenu.open', (open) => open && $nextTick(() => $refs.closeMenu.focus()))"
        class="absolute inset-y-0 start-0 flex w-[85%] max-w-sm flex-col bg-sand-50 shadow-xl"
    >
        <div class="flex h-16 items-center justify-between border-b border-sand-200 px-4">
            <x-store.logo />

            <button
                type="button"
                x-ref="closeMenu"
                class="-me-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100"
                x-on:click="$store.mobileMenu.hide()"
            >
                <x-icon name="close" class="size-6" />
                <span class="sr-only">{{ __('Close menu') }}</span>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-4" aria-label="{{ __('Main') }}">
            <a href="{{ route('shop') }}" class="flex h-12 items-center justify-between rounded-xl px-3 text-base font-medium hover:bg-sand-100">
                {{ __('Shop all') }}
                <x-icon name="chevron-end" class="size-4 text-ink-300" />
            </a>

            @foreach ($navigation['categories'] as $category)
                <div x-data="{ open: false }">
                    <div class="flex items-center">
                        <a href="{{ route('category.show', $category) }}" class="flex h-12 flex-1 items-center gap-3 rounded-xl px-3 text-base font-medium hover:bg-sand-100">
                            @if ($category->image_path)
                                <img src="{{ $category->imageUrl('image_path') }}" alt="" class="size-9 rounded-lg object-cover" loading="lazy">
                            @endif
                            {{ $category->translated('name') }}
                        </a>
                        @if ($category->children->isNotEmpty())
                            <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" class="inline-flex size-12 items-center justify-center rounded-xl text-ink-500 hover:bg-sand-100">
                                <x-icon name="chevron-down" class="size-4 transition" x-bind:class="open && 'rotate-180'" />
                                <span class="sr-only">{{ __('Show :category sections', ['category' => $category->translated('name')]) }}</span>
                            </button>
                        @endif
                    </div>
                    @if ($category->children->isNotEmpty())
                        <div x-show="open" x-cloak class="ms-6 border-s border-sand-200 ps-2">
                            @foreach ($category->children as $child)
                                <a href="{{ route('category.show', $child) }}" class="flex h-11 items-center rounded-xl px-3 text-sm hover:bg-sand-100">{{ $child->translated('name') }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($navigation['collections']->isNotEmpty())
                <p class="px-3 pt-5 pb-1 text-xs font-medium text-ink-500">{{ __('Collections') }}</p>
                @foreach ($navigation['collections'] as $collection)
                    <a href="{{ route('collection.show', $collection) }}" class="flex h-11 items-center rounded-xl px-3 text-sm font-medium text-maroon-700 hover:bg-sand-100">{{ $collection->translated('name') }}</a>
                @endforeach
            @endif

            <a href="{{ route('wishlist') }}" class="mt-4 flex h-11 items-center gap-3 rounded-xl px-3 text-sm hover:bg-sand-100">
                <x-icon name="heart" class="size-5" />
                {{ __('Wishlist') }}
            </a>
        </nav>

        <div class="border-t border-sand-200 p-4">
            <x-store.language-switcher />
        </div>
    </div>
</div>
