{{-- Local-only design review page. Not linked from the store and not translated. --}}
@php
    $tokens = [
        'Sand' => ['bg-sand-50' => '#FBF8F3', 'bg-sand-100' => '#F5EFE6', 'bg-sand-200' => '#EADFCF', 'bg-sand-300' => '#D9C8AF'],
        'Ink' => ['bg-ink-300' => '#A39A91', 'bg-ink-500' => '#6B625A', 'bg-ink-700' => '#3D3631', 'bg-ink-900' => '#1E1A17'],
        'Maroon' => ['bg-maroon-50' => '#F8EDF0', 'bg-maroon-600' => '#8E2741', 'bg-maroon-700' => '#7A1E35', 'bg-maroon-800' => '#63172A'],
        'Gold and status' => ['bg-gold-300' => '#DCC39B', 'bg-gold-500' => '#B8925A', 'bg-success-700' => '#2F6B4F', 'bg-danger-700' => '#B42318'],
    ];
    $sampleColors = [
        ['name' => 'Navy', 'hex' => '#1F2A44', 'available' => true],
        ['name' => 'Olive', 'hex' => '#5B6B3A', 'available' => true],
        ['name' => 'Rose', 'hex' => '#C98B92', 'available' => true],
        ['name' => 'Ivory', 'hex' => '#F2EBDD', 'available' => false],
    ];
    $sampleSizes = [['52', true], ['54', true], ['56', false], ['58', true], ['60', true]];
@endphp

<x-store.layout title="Style guide">
    <div class="page-container space-y-16 py-12">
        <header>
            <p class="text-sm font-medium text-maroon-700">Phase 1 · Design review</p>
            <h1 class="mt-2 font-display text-4xl">Style guide</h1>
            <p class="mt-2 max-w-xl text-ink-500">
                Proposed colors, type and components. Switch language in the header to review right-to-left layout.
            </p>
        </header>

        <section>
            <h2 class="text-lg font-semibold">Colors</h2>
            <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($tokens as $group => $swatches)
                    <div>
                        <h3 class="text-sm text-ink-500">{{ $group }}</h3>
                        <ul class="mt-2 space-y-2">
                            @foreach ($swatches as $class => $hex)
                                <li class="flex items-center gap-3 text-sm">
                                    <span class="{{ $class }} size-10 rounded-lg border border-ink-900/10"></span>
                                    <span dir="ltr">{{ $hex }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-8 lg:grid-cols-2">
            <div lang="en" dir="ltr" class="rounded-2xl border border-sand-200 bg-white p-6">
                <p class="text-xs text-ink-500">English · Fraunces + IBM Plex Sans Arabic</p>
                <p class="mt-4 font-display text-4xl leading-tight">Jelabiyas in the colors you love</p>
                <p class="mt-3 font-display text-2xl">Eid collection</p>
                <p class="mt-3 text-base">Soft crepe fabric with hand-finished embroidery on the sleeves. Available in four colors.</p>
                <p class="mt-2 text-sm text-ink-500">Delivered across Qatar in 1–3 days.</p>
            </div>
            <div lang="ar" dir="rtl" class="rounded-2xl border border-sand-200 bg-white p-6">
                <p class="text-xs text-ink-500">العربية · Noto Kufi Arabic + IBM Plex Sans Arabic</p>
                <p class="mt-4 font-display text-4xl leading-tight">جلابيات بالألوان التي تحبينها</p>
                <p class="mt-3 font-display text-2xl">مجموعة العيد</p>
                <p class="mt-3 text-base">قماش كريب ناعم مع تطريز يدوي على الأكمام. متوفرة بأربعة ألوان.</p>
                <p class="mt-2 text-sm text-ink-500">توصيل إلى جميع مناطق قطر خلال ١–٣ أيام.</p>
            </div>
        </section>

        <section>
            <h2 class="text-lg font-semibold">Buttons</h2>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-store.button>Order now</x-store.button>
                <x-store.button variant="secondary">Add to bag</x-store.button>
                <x-store.button variant="outline">Size guide</x-store.button>
                <x-store.button variant="ghost">Continue shopping</x-store.button>
                <x-store.button size="sm">Small</x-store.button>
                <x-store.button size="lg">Large</x-store.button>
                <x-store.button disabled>Sold out</x-store.button>
            </div>
        </section>

        <section class="grid gap-10 lg:grid-cols-2">
            <div>
                <h2 class="text-lg font-semibold">Color and size pickers (Phase 3 preview)</h2>
                <div class="mt-4 rounded-2xl border border-sand-200 bg-white p-6" x-data="{ color: 'Navy', size: '54' }">
                    <p class="text-sm">Color: <span class="font-semibold" x-text="color"></span></p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($sampleColors as $color)
                            <button
                                type="button"
                                x-on:click="color = '{{ $color['name'] }}'"
                                x-bind:class="color === '{{ $color['name'] }}' ? 'ring-2 ring-ink-900 ring-offset-2' : 'ring-1 ring-ink-900/15'"
                                @class(['relative size-10 rounded-full', 'opacity-50' => ! $color['available']])
                                style="background-color: {{ $color['hex'] }}"
                                title="{{ $color['name'] }}"
                            >
                                @unless ($color['available'])
                                    <span class="absolute inset-0 m-auto h-px w-full rotate-45 bg-ink-900"></span>
                                @endunless
                                <span class="sr-only">{{ $color['name'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <p class="mt-6 text-sm">Size: <span class="font-semibold" x-text="size"></span></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($sampleSizes as [$size, $inStock])
                            <button
                                type="button"
                                @if ($inStock) x-on:click="size = '{{ $size }}'" @else disabled @endif
                                x-bind:class="size === '{{ $size }}' ? 'border-ink-900 bg-ink-900 text-white' : 'border-sand-300 bg-white'"
                                class="h-11 min-w-14 rounded-full border px-4 text-sm font-medium disabled:text-ink-300 disabled:line-through"
                            >{{ $size }}</button>
                        @endforeach
                    </div>
                    <p class="mt-4 text-sm font-medium text-danger-700">Only 2 left in Navy / 54</p>
                </div>
            </div>

            <div>
                <h2 class="text-lg font-semibold">Product card (Phase 3 preview)</h2>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    @foreach (['Classic jelabiya', 'Embroidered kaftan'] as $index => $productName)
                        <article>
                            <div class="bg-motif relative aspect-[4/5] overflow-hidden rounded-2xl bg-sand-200">
                                @if ($index === 0)
                                    <span class="absolute start-3 top-3 rounded-full bg-maroon-700 px-2.5 py-1 text-xs font-medium text-white">-15%</span>
                                @endif
                            </div>
                            <div class="mt-3 flex gap-1.5">
                                @foreach ($sampleColors as $color)
                                    <span class="size-4 rounded-full ring-1 ring-ink-900/15" style="background-color: {{ $color['hex'] }}"></span>
                                @endforeach
                            </div>
                            <h3 class="mt-2 text-sm font-medium">{{ $productName }}</h3>
                            <p class="mt-1 text-sm" dir="ltr">
                                <span class="font-semibold">QAR 185</span>
                                @if ($index === 0)
                                    <span class="ms-1 text-ink-500 line-through">QAR 220</span>
                                @endif
                            </p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-lg font-semibold">Form fields (checkout preview)</h2>
            <div class="mt-4 grid max-w-xl gap-4">
                <label class="block">
                    <span class="text-sm font-medium">Full name</span>
                    <input type="text" class="mt-1.5 block h-12 w-full rounded-xl border border-sand-300 bg-white px-4 text-base focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none" placeholder="Fatima Al-Kuwari">
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Mobile number</span>
                    <span class="mt-1.5 flex h-12 overflow-hidden rounded-xl border border-sand-300 bg-white focus-within:border-maroon-700 focus-within:ring-2 focus-within:ring-maroon-100" dir="ltr">
                        <span class="flex items-center border-e border-sand-200 bg-sand-50 px-3 text-sm text-ink-500">+974</span>
                        <input type="tel" inputmode="tel" class="w-full px-4 text-base focus:outline-none" placeholder="5555 1234">
                    </span>
                </label>
                <p class="rounded-xl bg-danger-50 px-4 py-3 text-sm text-danger-700">Enter a Qatar mobile number with 8 digits.</p>
            </div>
        </section>

        <section>
            <h2 class="text-lg font-semibold">Trust strip</h2>
            <x-store.trust-strip class="mt-4" />
        </section>
    </div>
</x-store.layout>
