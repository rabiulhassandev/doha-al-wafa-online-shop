{{--
    Shared product listing: shop, category, collection and search pages.
    Expects $heading, $description, $breadcrumbs, $pageUrl, $products, $facets and $filters;
    optionally $bannerUrl, $subcategories and $searchTerm.
--}}
@php
    $searchTerm ??= null;
    $listingUrl = function (\App\Support\ProductFilters $filters) use ($pageUrl, $searchTerm): string {
        $query = http_build_query(array_filter([...$filters->toQuery(), 'q' => $searchTerm]));

        return $query === '' ? $pageUrl : "{$pageUrl}?{$query}";
    };
    $selectedColors = $facets['colors']->whereIn('slug', $filters->colors);
    $selectedSizes = $facets['sizes']->whereIn('slug', $filters->sizes);
    $preferredColorIds = $selectedColors->modelKeys();
    $sortOptions = [
        'featured' => __('Featured'),
        'newest' => __('Newest'),
        'price_asc' => __('Price: low to high'),
        'price_desc' => __('Price: high to low'),
    ];
@endphp

<x-store.layout :title="$heading" :description="$description">
    @if ($bannerUrl ?? null)
        <img src="{{ $bannerUrl }}" alt="" class="aspect-[16/7] max-h-96 w-full object-cover" fetchpriority="high">
    @endif

    <div x-data="{ filtersOpen: false }" class="page-container py-6">
        <x-store.breadcrumbs :items="$breadcrumbs" />

        <div class="mt-4 flex flex-wrap items-end justify-between gap-x-4 gap-y-1">
            <h1 class="font-display text-3xl sm:text-4xl">{{ $heading }}</h1>
            <p class="text-sm text-ink-500">{{ trans_choice(':count product|:count products', $products->total(), ['count' => $products->total()]) }}</p>
        </div>

        @if ($description)
            <p class="mt-2 max-w-2xl text-ink-700">{{ $description }}</p>
        @endif

        @if (isset($subcategories) && $subcategories->isNotEmpty())
            <ul class="-mx-4 mt-4 flex gap-2 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
                @foreach ($subcategories as $subcategory)
                    <li class="shrink-0">
                        <a href="{{ route('category.show', $subcategory) }}" class="inline-flex h-10 items-center rounded-full border border-sand-300 bg-white px-4 text-sm hover:border-ink-900">{{ $subcategory->translated('name') }}</a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($searchTerm !== null)
            <form method="GET" action="{{ route('search') }}" class="mt-4 flex max-w-xl gap-2" role="search">
                <label for="search-page-input" class="sr-only">{{ __('Search') }}</label>
                <input id="search-page-input" type="search" name="q" value="{{ $searchTerm }}" maxlength="100" placeholder="{{ __('Search jelabiyas, colors…') }}" class="h-12 min-w-0 flex-1 rounded-xl border border-sand-300 bg-white px-4 text-base focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none">
                <x-store.button type="submit">{{ __('Search') }}</x-store.button>
            </form>
        @endif

        <div class="mt-6 flex items-center justify-between gap-3 border-y border-sand-200 py-3 lg:hidden">
            <button type="button" x-on:click="filtersOpen = true" class="inline-flex h-10 items-center gap-2 rounded-full border border-sand-300 bg-white px-4 text-sm font-medium">
                <x-icon name="settings" class="size-4" />
                {{ __('Filter') }}
                @if ($filters->activeCount() > 0)
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-ink-900 text-[11px] text-white">{{ $filters->activeCount() }}</span>
                @endif
            </button>
            @include('storefront.products.sort', ['idSuffix' => 'mobile'])
        </div>

        @if ($filters->activeCount() > 0)
            <ul class="mt-4 flex flex-wrap gap-2 text-sm">
                @foreach ($selectedColors as $color)
                    <li><a href="{{ $listingUrl($filters->without('color', $color->slug)) }}" class="inline-flex h-9 items-center gap-1.5 rounded-full bg-ink-900 ps-3 pe-2 text-white">{{ $color->translated('name') }} <x-icon name="close" class="size-4" /><span class="sr-only">{{ __('Remove filter') }}</span></a></li>
                @endforeach
                @foreach ($selectedSizes as $size)
                    <li><a href="{{ $listingUrl($filters->without('size', $size->slug)) }}" class="inline-flex h-9 items-center gap-1.5 rounded-full bg-ink-900 ps-3 pe-2 text-white">{{ __('Size :size', ['size' => $size->translated('name')]) }} <x-icon name="close" class="size-4" /><span class="sr-only">{{ __('Remove filter') }}</span></a></li>
                @endforeach
                @if ($filters->minPrice !== null || $filters->maxPrice !== null)
                    <li><a href="{{ $listingUrl($filters->without('price', '')) }}" class="inline-flex h-9 items-center gap-1.5 rounded-full bg-ink-900 ps-3 pe-2 text-white">{{ __('Price') }} <x-icon name="close" class="size-4" /><span class="sr-only">{{ __('Remove filter') }}</span></a></li>
                @endif
                @if ($filters->inStockOnly)
                    <li><a href="{{ $listingUrl($filters->without('in_stock', '')) }}" class="inline-flex h-9 items-center gap-1.5 rounded-full bg-ink-900 ps-3 pe-2 text-white">{{ __('In stock') }} <x-icon name="close" class="size-4" /><span class="sr-only">{{ __('Remove filter') }}</span></a></li>
                @endif
                <li><a href="{{ $listingUrl(new \App\Support\ProductFilters(sort: $filters->sort)) }}" class="inline-flex h-9 items-center px-2 text-ink-700 underline">{{ __('Clear all') }}</a></li>
            </ul>
        @endif

        <div class="mt-6 lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-10">
            <aside class="hidden lg:block" aria-label="{{ __('Filters') }}">
                <div class="sticky top-24">
                    @include('storefront.products.filters', ['idSuffix' => 'desktop'])
                </div>
            </aside>

            <div>
                <div class="mb-4 hidden justify-end lg:flex">
                    @include('storefront.products.sort', ['idSuffix' => 'desktop'])
                </div>

                @if ($products->isEmpty())
                    <div class="rounded-2xl border border-dashed border-sand-300 px-6 py-16 text-center">
                        <x-store.motif-divider />
                        <p class="mt-4 font-medium">
                            {{ $filters->activeCount() > 0 ? __('Nothing matches these filters.') : ($searchTerm ? __('No products match your search.') : __('No products here yet.')) }}
                        </p>
                        @if ($filters->activeCount() > 0)
                            <x-store.button :href="$listingUrl(new \App\Support\ProductFilters)" variant="outline" class="mt-5">{{ __('Clear filters') }}</x-store.button>
                        @else
                            <x-store.button :href="route('shop')" variant="outline" class="mt-5">{{ __('Browse all products') }}</x-store.button>
                        @endif
                    </div>
                @else
                    <div x-data="productListing(@js($products->nextPageUrl()))">
                        <div id="product-grid" x-ref="grid" class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 xl:grid-cols-4">
                            @foreach ($products as $product)
                                <x-store.product-card :product="$product" :preferred-color-ids="$preferredColorIds" :eager="$products->onFirstPage() && $loop->index < 2" />
                            @endforeach
                        </div>

                        @if ($products->hasMorePages())
                            <div class="mt-10 flex justify-center" x-show="nextUrl">
                                <a
                                    href="{{ $products->nextPageUrl() }}"
                                    x-bind:href="nextUrl"
                                    data-load-more
                                    x-on:click.prevent="loadMore()"
                                    class="inline-flex h-12 items-center gap-2 rounded-full border border-ink-900/15 bg-white px-6 text-sm font-medium hover:border-ink-900/40"
                                >
                                    <span x-show="! loading">{{ __('Load more') }}</span>
                                    <span x-show="loading" x-cloak>{{ __('Loading…') }}</span>
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Phone filter sheet --}}
        <div x-cloak x-show="filtersOpen" x-on:keydown.escape.window="filtersOpen = false" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('Filters') }}">
            <div x-show="filtersOpen" x-transition.opacity class="absolute inset-0 bg-ink-900/40" x-on:click="filtersOpen = false"></div>
            <div
                x-show="filtersOpen"
                x-transition:enter="transition duration-200 ease-out"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition duration-150 ease-in"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute inset-x-0 bottom-0 flex max-h-[85dvh] flex-col rounded-t-3xl bg-sand-50"
            >
                <div class="flex items-center justify-between border-b border-sand-200 px-5 py-3">
                    <h2 class="text-base font-semibold">{{ __('Filters') }}</h2>
                    <button type="button" x-on:click="filtersOpen = false" class="-me-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100">
                        <x-icon name="close" class="size-6" />
                        <span class="sr-only">{{ __('Close filters') }}</span>
                    </button>
                </div>
                <div class="overflow-y-auto px-5 py-4">
                    @include('storefront.products.filters', ['idSuffix' => 'mobile'])
                </div>
            </div>
        </div>
    </div>
</x-store.layout>
