<div
    x-data="searchBox(@js(route('search.suggestions')))"
    x-on:open-search.window="show()"
    x-on:keydown.escape.window="close()"
    x-cloak
    x-show="open"
    class="fixed inset-0 z-50"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Search') }}"
>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink-900/40" x-on:click="close()"></div>

    <div x-show="open" x-transition class="relative bg-sand-50 shadow-xl">
        <form method="GET" action="{{ route('search') }}" class="page-container flex h-16 items-center gap-2" role="search">
            <x-icon name="search" class="size-5 text-ink-500" />
            <label for="search-input" class="sr-only">{{ __('Search') }}</label>
            <input
                id="search-input"
                x-ref="input"
                x-model="query"
                x-on:input.debounce.250ms="search()"
                type="search"
                name="q"
                autocomplete="off"
                enterkeyhint="search"
                maxlength="100"
                placeholder="{{ __('Search products, categories or collections…') }}"
                class="h-12 min-w-0 flex-1 bg-transparent text-base focus:outline-none"
            >
            <x-store.button type="submit" size="sm">{{ __('Search') }}</x-store.button>
            <button type="button" x-on:click="close()" class="-me-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100">
                <x-icon name="close" class="size-6" />
                <span class="sr-only">{{ __('Close search') }}</span>
            </button>
        </form>

        <div x-show="query.trim().length >= 2" class="border-t border-sand-200">
            <div class="page-container max-h-[70dvh] overflow-y-auto py-4">
                <p x-show="loading || searchedQuery !== query.trim()" class="text-sm text-ink-500" role="status">{{ __('Searching…') }}</p>

                <div x-show="! loading && searchedQuery === query.trim()">
                    <p x-show="failed" class="text-sm text-ink-500" role="status">{{ __('Suggestions are unavailable. Select Search to see results.') }}</p>
                    <p x-show="! failed && results.products.length === 0 && results.categories.length === 0 && results.collections.length === 0" class="text-sm text-ink-500" role="status">{{ __('No matches found.') }}</p>

                    @foreach (['categories' => __('Categories'), 'collections' => __('Collections')] as $group => $label)
                        <div x-show="results.{{ $group }}.length > 0" class="mb-3">
                            <h2 class="mb-2 text-sm font-semibold">{{ $label }}</h2>
                            <ul class="flex flex-wrap gap-2">
                                <template x-for="item in results.{{ $group }}" :key="item.url">
                                    <li>
                                        <a x-bind:href="item.url" x-text="item.name" class="inline-flex h-9 items-center rounded-full border border-sand-300 bg-white px-3 text-sm hover:border-ink-900"></a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    @endforeach

                    <ul class="divide-y divide-sand-200">
                        <template x-for="product in results.products" :key="product.url">
                            <li>
                                <a x-bind:href="product.url" class="flex items-center gap-3 py-2.5">
                                    <img x-bind:src="product.image" alt="" class="h-15 w-12 shrink-0 rounded-lg bg-sand-100 object-cover">
                                    <span class="min-w-0 flex-1">
                                        <span x-text="product.name" class="block truncate text-sm font-medium"></span>
                                        <span x-text="product.price" class="block text-sm text-ink-500"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
