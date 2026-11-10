@inject('settings', 'App\Services\Settings')

@php
    use App\Support\Money;

    $locale = app()->getLocale();
    $name = $product->translated('name');
    [$lowestPrice, $highestPrice] = $priceRange;
    $priceText = $lowestPrice === $highestPrice ? Money::format($lowestPrice, $locale) : __('From :price', ['price' => Money::format($lowestPrice, $locale)]);
    $compareAtText = $product->compare_at_price !== null && $product->compare_at_price > $lowestPrice ? Money::format($product->compare_at_price, $locale) : null;
    $whatsApp = $settings->phone('whatsapp_number');
    $sizeGuide = $product->sizeGuide;
    $coverImage = $selectedColor->images->first();
    $deliveryText = __('Delivered to your door across Qatar');
    if ($deliveryWindow !== null) {
        [$deliveryFrom, $deliveryTo] = $deliveryWindow;
        $deliveryText = $deliveryFrom->isSameDay($deliveryTo)
            ? __('Delivered across Qatar on :date', ['date' => $deliveryFrom->locale($locale)->translatedFormat('D j M')])
            : __('Delivered across Qatar between :from and :to', [
                'from' => $deliveryFrom->locale($locale)->translatedFormat('D j M'),
                'to' => $deliveryTo->locale($locale)->translatedFormat('D j M'),
            ]);
    }
    $data = $pageData + [
        'priceText' => $priceText,
        'compareAtText' => $compareAtText,
        'whatsApp' => $whatsApp?->whatsAppNumber(),
        'messages' => [
            'chooseSize' => __('Choose a size'),
            'inStock' => __('In stock'),
            'onlyLeft' => __('Only :count left'),
            'sizeSoldOut' => __('Sold out in this size'),
            'colorSoldOut' => __('This color is sold out'),
            'whatsApp' => __('Hello! I am interested in :product (:details). :url'),
            'addFailed' => __('Could not add to your bag. Please try again.'),
        ],
    ];
@endphp

@push('head')
    <link rel="canonical" href="{{ route('products.show', $product) }}">
    {{-- Shared links preview the color that was open, with its own photo. --}}
    <meta property="product:price:amount" content="{{ Money::toInput($lowestPrice) }}">
    <meta property="product:price:currency" content="QAR">
    <meta property="product:availability" content="{{ $selectedColor->offeredVariants()->contains(fn ($variant): bool => $variant->stock > 0) ? 'in stock' : 'out of stock' }}">
    @if ($isPreview)
        <meta name="robots" content="noindex">
    @endif
@endpush

<x-store.layout
    :title="$product->translated('meta_title') ?: $name"
    :description="$product->translated('meta_description') ?: \Illuminate\Support\Str::limit((string) $product->translated('description'), 155)"
    og-type="product"
    :og-title="$colors->count() > 1 ? $name.' – '.$selectedColor->displayName() : $name"
    :og-url="route('products.show', ['product' => $product, 'color' => $selectedColor->color->slug])"
    :og-image="$coverImage->url(1200)"
    :og-image-alt="$name.' – '.$selectedColor->displayName()"
    :whats-app-button="false"
>
    <x-store.track-event :event="$trackingEvent" />

    <div x-data="productPage(@js($data))" class="pb-28 lg:pb-12">
        @if ($isPreview)
            <p class="bg-gold-300/40 px-4 py-2 text-center text-sm">{{ __('Preview: shoppers cannot see this product until it is set to Active.') }}</p>
        @endif

        <div class="page-container pt-4">
            <x-store.breadcrumbs :items="$breadcrumbs" />
        </div>

        <div class="page-container mt-4 lg:grid lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-start lg:gap-12">
            {{-- Gallery: one per color, only the chosen one is shown. Hidden photos are not downloaded. --}}
            <div class="-mx-4 sm:mx-0">
                @foreach ($colors as $productColor)
                    @php($slug = $productColor->color->slug)
                    <div x-show="color === @js($slug)" @unless ($productColor->is($selectedColor)) style="display: none" @endunless>
                        <div
                            data-gallery="{{ $slug }}"
                            x-on:scroll.debounce.50ms="onGalleryScroll($el)"
                            class="flex snap-x snap-mandatory overflow-x-auto [scrollbar-width:none] lg:grid lg:grid-cols-2 lg:gap-3 lg:overflow-visible [&::-webkit-scrollbar]:hidden"
                        >
                            @foreach ($productColor->images as $image)
                                <button
                                    type="button"
                                    x-on:click="openLightbox({{ $loop->index }})"
                                    @class(['w-full shrink-0 snap-center lg:w-auto', 'lg:col-span-2' => $loop->first])
                                >
                                    <img
                                        src="{{ $image->url(800) }}"
                                        srcset="{{ $image->srcset() }}"
                                        sizes="(min-width: 1024px) 55vw, 100vw"
                                        alt="{{ $name }} – {{ $productColor->displayName() }}"
                                        width="1200"
                                        height="1500"
                                        class="aspect-[4/5] w-full bg-sand-100 object-cover sm:rounded-2xl"
                                        @if ($loop->first && $productColor->is($selectedColor)) fetchpriority="high" @else loading="lazy" decoding="async" @endif
                                    >
                                    <span class="sr-only">{{ __('Enlarge photo :number', ['number' => $loop->iteration]) }}</span>
                                </button>
                            @endforeach
                        </div>

                        @if ($productColor->images->count() > 1)
                            <div class="mt-3 flex justify-center gap-1.5 lg:hidden" aria-hidden="true">
                                @foreach ($productColor->images as $image)
                                    <span class="h-1.5 rounded-full transition-all" x-bind:class="imageIndex === {{ $loop->index }} ? 'w-5 bg-ink-900' : 'w-1.5 bg-ink-300'"></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6 space-y-6 lg:sticky lg:top-24 lg:mt-0">
                <div>
                    @if ($product->category->is_active)
                        <a href="{{ route('category.show', $product->category) }}" class="text-sm text-ink-500 hover:underline">{{ $product->category->translated('name') }}</a>
                    @endif
                    <h1 class="mt-1 font-display text-3xl leading-tight text-balance">{{ $name }}</h1>
                    <p class="mt-3 flex flex-wrap items-baseline gap-x-3">
                        <span class="text-2xl font-semibold" x-text="selected ? selected.price : priceText">{{ $priceText }}</span>
                        <span class="text-ink-500 line-through" x-show="selected ? selected.compareAt : compareAtText" x-text="selected ? selected.compareAt : compareAtText" @unless ($compareAtText) style="display: none" @endunless>{{ $compareAtText }}</span>
                    </p>
                </div>

                <fieldset>
                    <legend class="text-sm">{{ __('Color') }}: <span class="font-semibold" x-text="current.name">{{ $selectedColor->displayName() }}</span></legend>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($colors as $productColor)
                            @php($slug = $productColor->color->slug)
                            <button
                                type="button"
                                x-on:click="selectColor(@js($slug))"
                                x-bind:aria-pressed="(color === @js($slug)).toString()"
                                x-bind:class="color === @js($slug) ? 'ring-2 ring-ink-900 ring-offset-2 ring-offset-sand-50' : 'ring-1 ring-ink-900/15'"
                                class="relative size-11 overflow-hidden rounded-full"
                                style="background-color: {{ $productColor->color->hex }}"
                                title="{{ $productColor->displayName() }}"
                            >
                                @if ($productColor->color->swatch_path)
                                    <img src="{{ $productColor->color->imageUrl('swatch_path') }}" alt="" class="size-full object-cover">
                                @endif
                                @unless ($productColor->isInStock())
                                    <span class="absolute inset-0 m-auto h-px w-full -rotate-45 bg-ink-900/70" aria-hidden="true"></span>
                                @endunless
                                <span class="sr-only">{{ $productColor->displayName() }}@unless ($productColor->isInStock()) ({{ __('Sold out') }})@endunless</span>
                            </button>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset id="size-picker" class="scroll-mt-24">
                    <div class="flex items-center justify-between gap-3">
                        <legend class="text-sm">{{ __('Size') }}: <span class="font-semibold" x-text="sizeName(size) ?? messages.chooseSize">{{ __('Choose a size') }}</span></legend>
                        @if ($sizeGuide)
                            <button type="button" x-on:click="sizeGuideOpen = true" class="inline-flex items-center gap-1.5 text-sm font-medium underline underline-offset-4">
                                <x-icon name="ruler" class="size-4" />
                                {{ __('Size guide') }}
                            </button>
                        @endif
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($sizes as $size)
                            <button
                                type="button"
                                x-show="isOffered(@js($size->slug))"
                                x-on:click="selectSize(@js($size->slug))"
                                x-bind:disabled="! isAvailable(@js($size->slug))"
                                x-bind:aria-pressed="(size === @js($size->slug)).toString()"
                                x-bind:class="size === @js($size->slug) ? 'border-ink-900 bg-ink-900 text-white' : 'border-sand-300 bg-white'"
                                class="h-11 min-w-14 rounded-full border px-4 text-sm font-medium disabled:cursor-not-allowed disabled:text-ink-300 disabled:line-through"
                            >{{ $size->translated('name') }}</button>
                        @endforeach
                    </div>
                    <p x-show="sizeError" class="mt-3 text-sm font-medium text-danger-700" role="alert" style="display: none">{{ __('Choose a size first.') }}</p>
                    <p x-show="statusText && ! sizeError" x-text="statusText" x-bind:class="statusTone === 'warning' ? 'text-danger-700' : 'text-success-700'" class="mt-3 text-sm font-medium" role="status" style="display: none"></p>
                </fieldset>

                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex h-14 shrink-0 items-center rounded-full border border-sand-300 bg-white" role="group" aria-label="{{ __('Quantity') }}">
                            <button type="button" x-on:click="changeQuantity(-1)" x-bind:disabled="quantity <= 1" class="inline-flex size-12 items-center justify-center rounded-full text-lg disabled:opacity-30">
                                <span aria-hidden="true">−</span>
                                <span class="sr-only">{{ __('One less') }}</span>
                            </button>
                            <span class="min-w-7 text-center font-medium tabular-nums" x-text="quantity" aria-live="polite">1</span>
                            <button type="button" x-on:click="changeQuantity(1)" x-bind:disabled="quantity >= maxQuantity" class="inline-flex size-12 items-center justify-center rounded-full text-lg disabled:opacity-30">
                                <span aria-hidden="true">+</span>
                                <span class="sr-only">{{ __('One more') }}</span>
                            </button>
                        </div>
                        <x-store.button variant="secondary" size="lg" class="flex-1" x-on:click="addToBag()" x-bind:disabled="adding">
                            <span x-show="! adding">{{ __('Add to bag') }}</span>
                            <span x-show="adding" style="display: none">{{ __('Adding…') }}</span>
                        </x-store.button>
                    </div>

                    <form method="POST" action="{{ route('checkout.buy-now') }}" x-on:submit="requireSize() || $event.preventDefault()">
                        @csrf
                        <input type="hidden" name="variant_id" x-bind:value="selected?.variantId ?? ''">
                        <input type="hidden" name="quantity" x-bind:value="quantity">
                        <x-store.button type="submit" size="lg" class="w-full">{{ __('Order now') }}</x-store.button>
                    </form>

                    <p x-show="notice" x-text="notice" class="text-sm font-medium text-danger-700" role="alert" style="display: none"></p>
                    @error('variant_id')
                        <p class="text-sm font-medium text-danger-700" role="alert">{{ $message }}</p>
                    @enderror

                    @if ($whatsApp)
                        <a x-bind:href="whatsAppUrl" href="https://wa.me/{{ $whatsApp->whatsAppNumber() }}" target="_blank" rel="noopener" class="flex h-12 w-full items-center justify-center gap-2 rounded-full border border-success-700/30 bg-success-50 text-sm font-medium text-success-700 hover:border-success-700">
                            <x-icon name="whatsapp" class="size-5" />
                            {{ __('Ask about this on WhatsApp') }}
                        </a>
                    @endif

                    <div class="flex items-center justify-center gap-2">
                        <button type="button" x-on:click="$store.wishlist.toggle(productId)" x-bind:aria-pressed="$store.wishlist.has(productId).toString()" class="inline-flex h-10 items-center gap-2 rounded-full px-4 text-sm font-medium hover:bg-sand-100">
                            <svg class="size-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round" aria-hidden="true" x-bind:fill="$store.wishlist.has(productId) ? 'currentColor' : 'none'" x-bind:class="$store.wishlist.has(productId) && 'text-maroon-700'" fill="none">
                                <path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7.5 2.8C19.5 15.4 12 20 12 20z" />
                            </svg>
                            <span x-text="$store.wishlist.has(productId) ? @js(__('Saved')) : @js(__('Save'))">{{ __('Save') }}</span>
                        </button>
                        <button type="button" x-on:click="share()" class="inline-flex h-10 items-center gap-2 rounded-full px-4 text-sm font-medium hover:bg-sand-100">
                            <x-icon name="external" class="size-5" />
                            <span x-text="copied ? @js(__('Link copied')) : @js(__('Share'))">{{ __('Share') }}</span>
                        </button>
                    </div>
                </div>

                <ul class="grid gap-2 rounded-2xl bg-white p-4 text-sm">
                    <li class="flex items-center gap-3"><x-icon name="cash" class="size-5 text-maroon-700" />{{ __('Pay in cash when your order arrives') }}</li>
                    <li class="flex items-center gap-3">
                        <x-icon name="truck" class="size-5 text-maroon-700" />
                        {{ $deliveryText }}
                    </li>
                </ul>

                <div class="divide-y divide-sand-200 border-y border-sand-200">
                    @if ($product->translated('description'))
                        <details class="group py-4" open>
                            <summary class="flex cursor-pointer list-none items-center justify-between font-medium">
                                {{ __('Description') }}
                                <x-icon name="chevron-down" class="size-4 transition group-open:rotate-180" />
                            </summary>
                            <p class="mt-3 text-sm leading-relaxed whitespace-pre-line text-ink-700">{{ $product->translated('description') }}</p>
                        </details>
                    @endif
                    @if ($product->translated('details'))
                        <details class="group py-4">
                            <summary class="flex cursor-pointer list-none items-center justify-between font-medium">
                                {{ __('Fabric and care') }}
                                <x-icon name="chevron-down" class="size-4 transition group-open:rotate-180" />
                            </summary>
                            <p class="mt-3 text-sm leading-relaxed whitespace-pre-line text-ink-700">{{ $product->translated('details') }}</p>
                        </details>
                    @endif
                    <details class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between font-medium">
                            {{ __('Delivery and payment') }}
                            <x-icon name="chevron-down" class="size-4 transition group-open:rotate-180" />
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-ink-700">{{ __('We deliver to every area of Qatar. You pay in cash when your order arrives, so there is nothing to pay online.') }}</p>
                    </details>
                </div>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="page-container mt-16">
                <x-store.section-heading :title="__('You may also like')" />
                <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($related as $relatedProduct)
                        <x-store.product-card :product="$relatedProduct" />
                    @endforeach
                </div>
            </section>
        @endif

        <section
            x-data="productCards({ url: @js(route('product-cards')), source: 'recent', exclude: {{ $product->id }}, limit: 4 })"
            x-show="! empty"
            class="page-container mt-16"
            style="display: none"
        >
            <x-store.section-heading :title="__('Recently viewed')" />
            <div x-ref="grid" class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4"></div>
        </section>

        {{-- Phone bar that keeps the price and buttons within reach. --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-sand-200 bg-white/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur lg:hidden">
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold" x-text="selected ? selected.price : priceText">{{ $priceText }}</p>
                    <p class="truncate text-xs text-ink-500"><span x-text="current.name">{{ $selectedColor->displayName() }}</span><span x-show="size"> · <span x-text="sizeName(size)"></span></span></p>
                </div>
                <x-store.button size="md" x-on:click="addToBag()" x-bind:disabled="adding">{{ __('Add to bag') }}</x-store.button>
            </div>
        </div>

        @if ($sizeGuide)
            @include('storefront.products.size-guide', ['sizeGuide' => $sizeGuide])
        @endif

        {{-- Full-screen photos. Pinch to zoom works natively. --}}
        <div x-cloak x-show="lightboxOpen" x-on:keydown.escape.window="lightboxOpen = false" class="fixed inset-0 z-50 bg-ink-900" role="dialog" aria-modal="true" aria-label="{{ __('Photos') }}">
            <div x-ref="lightboxTrack" class="flex h-full snap-x snap-mandatory overflow-x-auto">
                <template x-for="(image, index) in current.images" :key="color + index">
                    <div class="flex h-full w-full shrink-0 snap-center items-center justify-center">
                        <img x-bind:src="image.src" alt="" class="max-h-full w-full object-contain">
                    </div>
                </template>
            </div>
            <button type="button" x-on:click="lightboxOpen = false" class="absolute end-3 top-3 inline-flex size-11 items-center justify-center rounded-full bg-white/90 text-ink-900">
                <x-icon name="close" class="size-6" />
                <span class="sr-only">{{ __('Close photos') }}</span>
            </button>
        </div>
    </div>
</x-store.layout>
