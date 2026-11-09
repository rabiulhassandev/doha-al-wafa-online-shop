@props(['banners'])

{{-- Swipeable slides using CSS scroll snapping, so no slider library is needed. --}}
<section
    x-data="{ index: 0, count: {{ $banners->count() }} }"
    class="relative"
    aria-roledescription="carousel"
    aria-label="{{ __('Highlights') }}"
>
    <div
        x-ref="track"
        x-on:scroll.debounce.60ms="index = Math.round(Math.abs($el.scrollLeft) / $el.clientWidth)"
        class="flex snap-x snap-mandatory overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
    >
        @foreach ($banners as $banner)
            @php($url = $banner->url())
            <div class="relative w-full shrink-0 snap-center" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}">
                <picture>
                    @if ($banner->mobile_image_path)
                        <source media="(max-width: 767px)" srcset="{{ $banner->imageUrl('mobile_image_path') }}">
                    @endif
                    <img
                        src="{{ $banner->imageUrl('image_path') }}"
                        alt="{{ $banner->translated('title') ?? '' }}"
                        @class([
                            'w-full object-cover',
                            'aspect-[4/5] md:aspect-[16/7]' => $banner->mobile_image_path,
                            'aspect-[4/3] md:aspect-[16/7]' => ! $banner->mobile_image_path,
                        ])
                        @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                    >
                </picture>

                @if ($banner->translated('title') || $banner->translated('button_label'))
                    <div class="absolute inset-0 flex items-end bg-gradient-to-t from-ink-900/60 via-ink-900/10 to-transparent md:items-center md:bg-gradient-to-r md:rtl:bg-gradient-to-l">
                        <div class="page-container pb-12 text-white md:pb-0">
                            <div class="max-w-lg">
                                @if ($banner->translated('title'))
                                    <h2 class="font-display text-3xl leading-tight text-balance sm:text-5xl">{{ $banner->translated('title') }}</h2>
                                @endif
                                @if ($banner->translated('subtitle'))
                                    <p class="mt-3 text-base text-white/85">{{ $banner->translated('subtitle') }}</p>
                                @endif
                                @if ($url && $banner->translated('button_label'))
                                    <x-store.button :href="$url" variant="light" class="mt-6">{{ $banner->translated('button_label') }}</x-store.button>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($url)
                    <a href="{{ $url }}" class="absolute inset-0"><span class="sr-only">{{ __('Open') }}</span></a>
                @endif
            </div>
        @endforeach
    </div>

    @if ($banners->count() > 1)
        <div class="absolute inset-x-0 bottom-4 flex justify-center gap-2">
            @foreach ($banners as $banner)
                <button
                    type="button"
                    x-on:click="$refs.track.children[{{ $loop->index }}].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' })"
                    x-bind:class="index === {{ $loop->index }} ? 'w-6 bg-white' : 'w-2 bg-white/60'"
                    class="h-2 rounded-full transition-all"
                >
                    <span class="sr-only">{{ __('Show slide :number', ['number' => $loop->iteration]) }}</span>
                </button>
            @endforeach
        </div>
    @endif
</section>
