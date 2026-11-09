<article
    x-data="{ image: @js($image), href: @js($url) }"
    {{ $attributes->merge(['class' => 'group relative flex flex-col']) }}
>
    <a href="{{ $url }}" x-bind:href="href" class="relative block overflow-hidden rounded-2xl bg-sand-100">
        @if ($image)
            <img
                src="{{ $image['src'] }}"
                srcset="{{ $image['srcset'] }}"
                sizes="(min-width: 1024px) 23vw, (min-width: 640px) 31vw, 47vw"
                x-bind:src="image.src"
                x-bind:srcset="image.srcset"
                alt="{{ $name }}"
                width="800"
                height="1000"
                @if ($eager) fetchpriority="high" @else loading="lazy" decoding="async" @endif
                @class(['aspect-[4/5] w-full object-cover transition duration-500 group-hover:scale-[1.03]', 'opacity-60' => ! $inStock])
            >
        @endif

        <span class="absolute start-2 top-2 flex flex-col items-start gap-1">
            @unless ($inStock)
                <span class="rounded-full bg-ink-900/85 px-2.5 py-1 text-xs font-medium text-white">{{ __('Sold out') }}</span>
            @elseif ($discountPercent)
                <span class="rounded-full bg-maroon-700 px-2.5 py-1 text-xs font-medium text-white" dir="ltr">-{{ $discountPercent }}%</span>
            @endunless
        </span>
    </a>

    <x-store.wishlist-button :product-id="$product->id" class="absolute end-2 top-2" />

    @if (count($swatches) > 1)
        <div class="mt-2.5 flex items-center gap-1.5">
            @foreach ($swatches as $swatch)
                <button
                    type="button"
                    x-on:click="image = @js($swatch['image']); href = @js($swatch['url'])"
                    x-bind:class="href === @js($swatch['url']) ? 'ring-2 ring-ink-900 ring-offset-1' : 'ring-1 ring-ink-900/15'"
                    class="relative size-5 overflow-hidden rounded-full ring-1 ring-ink-900/15"
                    style="background-color: {{ $swatch['hex'] }}"
                    title="{{ $swatch['name'] }}"
                >
                    @if ($swatch['swatchUrl'])
                        <img src="{{ $swatch['swatchUrl'] }}" alt="" class="size-full object-cover" loading="lazy">
                    @endif
                    <span class="sr-only">{{ $swatch['name'] }}</span>
                </button>
            @endforeach
            @if ($hiddenSwatchCount > 0)
                <span class="text-xs text-ink-500" dir="ltr">+{{ $hiddenSwatchCount }}</span>
            @endif
        </div>
    @endif

    <h3 class="mt-2 text-sm leading-snug font-medium">
        <a href="{{ $url }}" x-bind:href="href" class="hover:underline">{{ $name }}</a>
    </h3>

    <p class="mt-1 flex flex-wrap items-baseline gap-x-2 text-sm">
        <span class="font-semibold">
            @if ($isPriceRange)
                {{ __('From :price', ['price' => $price]) }}
            @else
                {{ $price }}
            @endif
        </span>
        @if ($compareAtPrice)
            <span class="text-xs text-ink-500 line-through">{{ $compareAtPrice }}</span>
        @endif
    </p>
</article>
