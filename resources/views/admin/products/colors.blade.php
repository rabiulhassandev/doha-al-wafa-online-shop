<x-admin.layout :title="'Photos & colors · '.$product->name_en">
    <x-admin.product-tabs :product="$product" current="colors" />

    @if ($product->colors->isEmpty() || ($product->colors->count() === 1 && $product->colors->first()->hasColor()))
        <x-admin.card title="Sell without colors" description="Use one photo gallery and track stock by size. If there is one color, its existing photos and stock are kept.">
            <form method="POST" action="{{ route('admin.products.without-color', $product) }}">
                @csrf
                <x-admin.button type="submit" variant="secondary">Use without color</x-admin.button>
            </form>
        </x-admin.card>
    @endif

    @php($sharedGallery = $product->colors->contains(fn ($gallery) => ! $gallery->hasColor()))
    <x-admin.card title="Colors (optional)" :description="$sharedGallery ? 'The first color you add takes over the existing product photos and stock.' : 'Each color gets its own photos and sizes.'">
        @if ($availableColors->isEmpty())
            <p class="text-sm text-ink-500">
                <a href="{{ route('admin.colors.create') }}" class="font-medium text-maroon-700 hover:underline">Add a color to the palette</a> if you want shoppers to choose a color.
            </p>
        @else
            <form method="POST" action="{{ route('admin.products.colors.store', $product) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="sm:w-72">
                    <x-admin.select
                        name="color_id"
                        label="Color"
                        :options="['' => 'Choose a color'] + $availableColors->pluck('name_en', 'id')->all()"
                    />
                </div>
                <x-admin.button type="submit">
                    <x-icon name="plus" class="size-4" />
                    {{ $sharedGallery ? 'Assign color to photos and stock' : 'Add color' }}
                </x-admin.button>
            </form>
        @endif
    </x-admin.card>

    @foreach ($product->colors as $productColor)
        <section id="color-{{ $productColor->id }}" class="scroll-mt-20 rounded-2xl border border-sand-200 bg-white shadow-sm">
            <header class="flex flex-wrap items-center gap-3 border-b border-sand-200 px-5 py-4 sm:px-6">
                @include('admin.colors.swatch', ['color' => $productColor->color, 'sizeClass' => 'size-8'])
                <div class="me-auto">
                    <h2 class="text-base font-semibold">{{ $productColor->hasColor() ? $productColor->nameEn() : 'Product photos' }}</h2>
                    <p class="text-xs text-ink-500">
                        {{ $productColor->images->count() }} {{ str('photo')->plural($productColor->images->count()) }}
                        @unless ($productColor->is_active)
                            · Hidden from shoppers
                        @endunless
                    </p>
                </div>

                @if ($productColor->hasColor())
                @if ($productColor->is_default)
                    <x-admin.badge color="maroon">Shown first</x-admin.badge>
                @else
                    <form method="POST" action="{{ route('admin.products.colors.default', [$product, $productColor]) }}">
                        @csrf
                        @method('PUT')
                        <x-admin.button type="submit" variant="ghost" size="sm">Show first</x-admin.button>
                    </form>
                @endif

                <div class="flex items-center">
                    @foreach (['up' => 'Move up', 'down' => 'Move down'] as $direction => $label)
                        <form method="POST" action="{{ route('admin.products.colors.position', [$product, $productColor]) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="direction" value="{{ $direction }}">
                            <button
                                type="submit"
                                class="inline-flex size-9 items-center justify-center rounded-lg text-ink-500 hover:bg-sand-100 disabled:opacity-30"
                                @disabled(($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last))
                            >
                                <x-icon name="chevron-down" :class="$direction === 'up' ? 'size-4 rotate-180' : 'size-4'" />
                                <span class="sr-only">{{ $label }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>

                <x-admin.button
                    :href="route('admin.short-links.create', [
                        'label' => $product->name_en.' – '.$productColor->nameEn(),
                        'destination' => route('products.show', ['locale' => 'ar', 'product' => $product, 'color' => $productColor->color->slug], false),
                    ])"
                    variant="ghost"
                    size="sm"
                >
                    <x-icon name="link" class="size-4" />
                    Short link
                </x-admin.button>

                <x-admin.confirm-button
                    :action="route('admin.products.colors.destroy', [$product, $productColor])"
                    :title="'Remove '.$productColor->nameEn().' from this product?'"
                    message="Its photos, sizes and stock for this product are deleted. The color stays in your palette."
                    confirm-label="Remove"
                >
                    <x-icon name="trash" class="size-4" />
                    Remove
                </x-admin.confirm-button>
                @endif
            </header>

            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div>
                    <h3 class="text-sm font-medium">Photos</h3>
                    <p class="mt-0.5 text-xs text-ink-500">The first photo is the cover. Photos are cropped to a 4:5 portrait shape.</p>

                    @if ($productColor->images->isNotEmpty())
                        <ul class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-4 xl:grid-cols-6">
                            @foreach ($productColor->images as $image)
                                <li class="group">
                                    <div class="relative aspect-[4/5] overflow-hidden rounded-lg bg-sand-100">
                                        <img src="{{ $image->url(400) }}" alt="" class="size-full object-cover" loading="lazy">
                                        @if ($loop->first)
                                            <span class="absolute start-1.5 top-1.5 rounded-full bg-ink-900/80 px-2 py-0.5 text-[11px] font-medium text-white">Cover</span>
                                        @endif
                                    </div>
                                    <div class="mt-1 flex items-center justify-between">
                                        <div class="flex">
                                            @foreach (['up' => 'Move earlier', 'down' => 'Move later'] as $direction => $label)
                                                <form method="POST" action="{{ route('admin.products.colors.images.position', [$product, $productColor, $image]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                                    <button
                                                        type="submit"
                                                        class="inline-flex size-8 items-center justify-center rounded-lg text-ink-500 hover:bg-sand-100 disabled:opacity-30"
                                                        @disabled(($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last))
                                                    >
                                                        <x-icon name="chevron-down" :class="$direction === 'up' ? 'size-4 rotate-90 rtl:-rotate-90' : 'size-4 -rotate-90 rtl:rotate-90'" />
                                                        <span class="sr-only">{{ $label }}</span>
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                        <x-admin.confirm-button
                                            :action="route('admin.products.colors.images.destroy', [$product, $productColor, $image])"
                                            title="Delete this photo?"
                                            message="It is removed from the product straight away."
                                            class="inline-flex size-8 items-center justify-center rounded-lg text-danger-700 hover:bg-danger-50"
                                        >
                                            <x-icon name="trash" class="size-4" />
                                            <span class="sr-only">Delete photo</span>
                                        </x-admin.confirm-button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($productColor->images->count() < \App\Http\Requests\Admin\ProductImageRequest::MAX_IMAGES_PER_COLOR)
                        <form
                            method="POST"
                            action="{{ route('admin.products.colors.images.store', [$product, $productColor]) }}"
                            enctype="multipart/form-data"
                            x-data="productImageUploader(@js(route('admin.products.colors.images.store', [$product, $productColor])))"
                            x-on:submit.prevent="upload($refs.files.files)"
                            class="mt-4 rounded-xl border border-dashed border-sand-300 bg-sand-50 p-4"
                        >
                            @csrf
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                                <div class="min-w-0 flex-1">
                                    <label for="images-{{ $productColor->id }}" class="block text-sm font-medium">Add photos</label>
                                    <input
                                        id="images-{{ $productColor->id }}"
                                        x-ref="files"
                                        type="file"
                                        name="images[]"
                                        accept="image/jpeg,image/png,image/webp"
                                        multiple
                                        required
                                        class="mt-1.5 block w-full text-sm text-ink-700 file:me-3 file:h-9 file:rounded-lg file:border-0 file:bg-white file:px-3 file:text-sm file:font-medium file:shadow-sm hover:file:bg-sand-100"
                                    >
                                </div>
                                <fieldset>
                                    <legend class="text-sm font-medium">Keep the</legend>
                                    <div class="mt-1.5 flex rounded-lg border border-sand-300 bg-white p-0.5 text-sm">
                                        @foreach (\App\Enums\ImageFocus::cases() as $focus)
                                            <label class="cursor-pointer rounded-md px-3 py-1.5 has-checked:bg-ink-900 has-checked:text-white">
                                                <input type="radio" name="focus" value="{{ $focus->value }}" x-model="focus" class="sr-only" @checked($focus === \App\Enums\ImageFocus::Center)>
                                                {{ $focus->label() }}
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                <x-admin.button type="submit" x-bind:disabled="busy">
                                    <span x-show="! busy">Upload</span>
                                    <span x-show="busy" x-cloak x-text="`Uploading ${done + 1} of ${total}…`"></span>
                                </x-admin.button>
                            </div>
                            <p class="mt-2 text-xs text-ink-500">JPG, PNG or WebP, at least 500 × 625 pixels. Large phone photos are shrunk before uploading.</p>
                            <p x-show="error" x-cloak x-text="error" class="mt-2 text-sm font-medium text-danger-700" role="alert"></p>
                            @error('images')
                                <p class="mt-2 text-sm font-medium text-danger-700">{{ $message }}</p>
                            @enderror
                            @error('images.*')
                                <p class="mt-2 text-sm font-medium text-danger-700">{{ $message }}</p>
                            @enderror
                        </form>
                    @else
                        <p class="mt-4 text-sm text-ink-500">This gallery has the maximum of {{ \App\Http\Requests\Admin\ProductImageRequest::MAX_IMAGES_PER_COLOR }} photos.</p>
                    @endif
                </div>

                @if ($productColor->hasColor())
                <form method="POST" action="{{ route('admin.products.colors.update', [$product, $productColor]) }}" class="space-y-4 rounded-xl bg-sand-50 p-4">
                    @csrf
                    @method('PUT')
                    <h3 class="text-sm font-medium">Name on this product</h3>
                    <x-admin.input
                        :name="'label_en'"
                        :id="'label_en_'.$productColor->id"
                        label="English (optional)"
                        :value="$productColor->label_en"
                        :placeholder="$productColor->color->name_en"
                        maxlength="60"
                    />
                    <x-admin.input
                        :name="'label_ar'"
                        :id="'label_ar_'.$productColor->id"
                        label="Arabic (optional)"
                        :value="$productColor->label_ar"
                        :placeholder="$productColor->color->name_ar"
                        maxlength="60"
                        dir="rtl"
                        lang="ar"
                    />
                    <x-admin.toggle name="is_active" label="Show this color to shoppers" :checked="$productColor->is_active" />
                    <x-admin.button type="submit" variant="secondary" size="sm">Save</x-admin.button>
                </form>
                @endif
            </div>
        </section>
    @endforeach

    @if ($product->colors->isNotEmpty())
        <div class="flex justify-end">
            <x-admin.button :href="route('admin.products.stock.edit', $product)">
                Next: sizes and stock
                <x-icon name="chevron-end" class="size-4" />
            </x-admin.button>
        </div>
    @endif
</x-admin.layout>
