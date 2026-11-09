{{-- Filter form. Expects $filters, $facets, $pageUrl, $searchTerm, $listingUrl and $idSuffix. --}}
<form method="GET" action="{{ $pageUrl }}" class="space-y-7">
    @if ($searchTerm)
        <input type="hidden" name="q" value="{{ $searchTerm }}">
    @endif
    @if ($filters->sort !== 'featured')
        <input type="hidden" name="sort" value="{{ $filters->sort }}">
    @endif

    @if ($facets['colors']->isNotEmpty())
        <fieldset>
            <legend class="text-sm font-semibold">{{ __('Color') }}</legend>
            <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2.5">
                @foreach ($facets['colors'] as $color)
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm">
                        <input type="checkbox" name="color[]" value="{{ $color->slug }}" @checked(in_array($color->slug, $filters->colors, true)) class="peer sr-only">
                        <span class="size-6 shrink-0 overflow-hidden rounded-full ring-1 ring-ink-900/15 ring-offset-2 ring-offset-sand-50 peer-checked:ring-2 peer-checked:ring-ink-900 peer-focus-visible:outline-2 peer-focus-visible:outline-maroon-700" style="background-color: {{ $color->hex }}">
                            @if ($color->swatch_path)
                                <img src="{{ $color->imageUrl('swatch_path') }}" alt="" class="size-full object-cover" loading="lazy">
                            @endif
                        </span>
                        <span class="peer-checked:font-semibold">{{ $color->translated('name') }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    @if ($facets['sizes']->isNotEmpty())
        <fieldset>
            <legend class="text-sm font-semibold">{{ __('Size') }}</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($facets['sizes'] as $size)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="size[]" value="{{ $size->slug }}" @checked(in_array($size->slug, $filters->sizes, true)) class="peer sr-only">
                        <span class="inline-flex h-10 min-w-12 items-center justify-center rounded-full border border-sand-300 bg-white px-3 text-sm font-medium peer-checked:border-ink-900 peer-checked:bg-ink-900 peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-maroon-700">{{ $size->translated('name') }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    <fieldset>
        <legend class="text-sm font-semibold">{{ __('Price (QAR)') }}</legend>
        <div class="mt-3 flex items-center gap-2" dir="ltr">
            <label for="min-{{ $idSuffix }}" class="sr-only">{{ __('Lowest price') }}</label>
            <input id="min-{{ $idSuffix }}" type="number" name="min" min="0" inputmode="numeric" value="{{ \App\Support\Money::toInput($filters->minPrice) }}" placeholder="{{ __('Min') }}" class="h-11 w-full rounded-xl border border-sand-300 bg-white px-3 text-sm focus:border-maroon-700 focus:outline-none">
            <span class="text-ink-300">–</span>
            <label for="max-{{ $idSuffix }}" class="sr-only">{{ __('Highest price') }}</label>
            <input id="max-{{ $idSuffix }}" type="number" name="max" min="0" inputmode="numeric" value="{{ \App\Support\Money::toInput($filters->maxPrice) }}" placeholder="{{ __('Max') }}" class="h-11 w-full rounded-xl border border-sand-300 bg-white px-3 text-sm focus:border-maroon-700 focus:outline-none">
        </div>
    </fieldset>

    <label class="flex cursor-pointer items-center justify-between gap-3 text-sm font-semibold">
        {{ __('In stock only') }}
        <input type="checkbox" name="in_stock" value="1" @checked($filters->inStockOnly) class="size-5 accent-maroon-700">
    </label>

    <div class="flex gap-3">
        <x-store.button type="submit" class="flex-1">{{ __('Show results') }}</x-store.button>
        @if ($filters->activeCount() > 0)
            <x-store.button :href="$listingUrl(new \App\Support\ProductFilters(sort: $filters->sort))" variant="ghost">{{ __('Clear') }}</x-store.button>
        @endif
    </div>
</form>
