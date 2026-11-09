<x-store.layout :description="__('Jelabiyas in many colors, delivered across Qatar. Pay cash on delivery.')">
    @if ($banners->isNotEmpty())
        <h1 class="sr-only">{{ __('Jelabiyas in the colors you love') }}</h1>
        <x-store.banner-slider :banners="$banners" />
    @else
        <section class="relative overflow-hidden bg-maroon-800 text-white">
            <div class="bg-motif-light absolute inset-0" aria-hidden="true"></div>
            <div class="page-container relative py-20 sm:py-28">
                <p class="text-sm font-medium text-gold-300 ltr:uppercase ltr:tracking-[0.2em]">{{ __('New season') }}</p>
                <h1 class="mt-4 max-w-xl font-display text-4xl leading-tight text-balance sm:text-5xl">{{ __('Jelabiyas in the colors you love') }}</h1>
                <p class="mt-5 max-w-md text-base text-white/80">{{ __('Pick your color and size, order in a minute, and pay in cash when it arrives.') }}</p>
                @if ($newArrivals->isNotEmpty())
                    <x-store.button :href="route('shop')" variant="light" size="lg" class="mt-8">{{ __('Shop the collection') }}</x-store.button>
                @endif
            </div>
        </section>
    @endif

    @if ($categories->isNotEmpty())
        <nav class="page-container pt-6" aria-label="{{ __('Categories') }}">
            <ul class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-2 [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
                @foreach ($categories as $category)
                    <li class="shrink-0">
                        <a href="{{ route('category.show', $category) }}" class="flex h-11 items-center gap-2 rounded-full border border-sand-300 bg-white ps-1.5 pe-4 text-sm font-medium hover:border-ink-900">
                            @if ($category->image_path)
                                <img src="{{ $category->imageUrl('image_path') }}" alt="" class="size-8 rounded-full object-cover" loading="lazy">
                            @else
                                <span class="size-2"></span>
                            @endif
                            {{ $category->translated('name') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if ($newArrivals->isNotEmpty())
        <section class="page-container py-10">
            <x-store.section-heading :title="__('New arrivals')" :href="route('shop', ['sort' => 'newest'])" />
            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($newArrivals as $product)
                    <x-store.product-card :product="$product" :eager="$loop->index < 2 && $banners->isEmpty()" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($colors->count() > 1)
        <section class="border-y border-sand-200 bg-white py-10">
            <div class="page-container">
                <x-store.section-heading :title="__('Shop by color')" />
                <ul class="mt-6 flex flex-wrap gap-x-5 gap-y-4">
                    @foreach ($colors as $color)
                        <li>
                            <a href="{{ route('shop', ['color' => $color->slug]) }}" class="group flex flex-col items-center gap-2 text-xs">
                                <span class="size-12 overflow-hidden rounded-full ring-1 ring-ink-900/15 transition group-hover:ring-2 group-hover:ring-ink-900" style="background-color: {{ $color->hex }}">
                                    @if ($color->swatch_path)
                                        <img src="{{ $color->imageUrl('swatch_path') }}" alt="" class="size-full object-cover" loading="lazy">
                                    @endif
                                </span>
                                {{ $color->translated('name') }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($collections->isNotEmpty())
        <section class="page-container py-10">
            <div @class(['grid gap-4', 'sm:grid-cols-2' => $collections->count() === 2, 'sm:grid-cols-3' => $collections->count() === 3])>
                @foreach ($collections as $collection)
                    <a href="{{ route('collection.show', $collection) }}" class="group relative block overflow-hidden rounded-2xl">
                        <img src="{{ $collection->imageUrl('banner_path') }}" alt="" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy">
                        <span class="absolute inset-0 flex items-end bg-gradient-to-t from-ink-900/60 to-transparent p-5">
                            <span class="font-display text-2xl text-white">{{ $collection->translated('name') }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($featured->isNotEmpty())
        <section class="page-container py-10">
            <x-store.section-heading :title="__('Our picks')" :href="route('shop')" />
            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($featured as $product)
                    <x-store.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="page-container py-10">
        <x-store.trust-strip />
    </section>

    @if ($newArrivals->isEmpty())
        <section class="page-container pb-6 text-center">
            <x-store.motif-divider />
            <h2 class="mt-6 font-display text-2xl sm:text-3xl">{{ __('Our collection is on its way') }}</h2>
            <p class="mx-auto mt-3 max-w-md text-ink-500">{{ __('We are photographing every jelabiya in every color. Follow us to see them first.') }}</p>
        </section>
    @endif
</x-store.layout>
