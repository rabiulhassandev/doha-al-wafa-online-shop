<x-admin.layout :title="'Stock · '.$product->name_en">
    <x-admin.product-tabs :product="$product" current="stock" />

    @if ($product->colors->isEmpty())
        <x-admin.card>
            <p class="text-sm text-ink-500">Set up product photos before choosing sizes.</p>
            <div class="mt-4">
                <x-admin.button :href="route('admin.products.colors.index', $product)" size="sm">Set up photos</x-admin.button>
            </div>
        </x-admin.card>
    @else
        @php
            $gridErrors = collect([...$errors->get('cells'), ...$errors->get('cells.*')])->flatten()->unique()->values();
        @endphp

        <form
            method="POST"
            action="{{ route('admin.products.stock.update', $product) }}"
            x-data="{ sizes: @js(array_map('intval', old('size_ids', $offeredSizeIds))) }"
            class="space-y-6"
        >
            @csrf
            @method('PUT')

            <x-admin.card title="Sizes this product comes in" description="Tick every size you sell. Untick a size to stop selling it; its stock history is kept.">
                <div class="flex flex-wrap gap-2">
                    @foreach ($sizes as $size)
                        <label class="inline-flex h-10 min-w-12 cursor-pointer items-center justify-center gap-2 rounded-lg border border-sand-300 bg-white px-3 text-sm font-medium has-checked:border-ink-900 has-checked:bg-ink-900 has-checked:text-white">
                            <input type="checkbox" name="size_ids[]" value="{{ $size->id }}" x-model.number="sizes" class="sr-only">
                            {{ $size->name_en }}
                        </label>
                    @endforeach
                </div>
                @if ($sizes->isEmpty())
                    <p class="text-sm text-ink-500">
                        No sizes yet. <a href="{{ route('admin.sizes.create') }}" class="font-medium text-maroon-700 hover:underline">Add sizes</a> first.
                    </p>
                @endif
            </x-admin.card>

            <x-admin.card title="Stock" description="Enter how many are available in each size. Untick “Sell” for any option you don't offer.">
                @if ($gridErrors->isNotEmpty())
                    <ul class="mb-4 space-y-1 rounded-xl bg-danger-50 px-4 py-3 text-sm text-danger-700" role="alert">
                        @foreach ($gridErrors as $gridError)
                            <li>{{ $gridError }}</li>
                        @endforeach
                    </ul>
                @endif

                <p x-show="sizes.length === 0" class="text-sm text-ink-500">Tick at least one size above.</p>

                <div x-show="sizes.length > 0" class="-mx-5 overflow-x-auto sm:-mx-6">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-xs font-medium text-ink-500">
                                <th scope="col" class="sticky start-0 z-10 bg-white px-5 py-2 text-start sm:px-6">{{ $product->colors->first()->hasColor() ? 'Color' : 'Product' }}</th>
                                @foreach ($sizes as $size)
                                    <th scope="col" x-show="sizes.includes({{ $size->id }})" class="px-2 py-2 text-center">{{ $size->name_en }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sand-200">
                            @foreach ($product->colors as $productColor)
                                <tr>
                                    <th scope="row" class="sticky start-0 z-10 bg-white px-5 py-3 text-start font-medium whitespace-nowrap sm:px-6">
                                        <span class="flex items-center gap-2">
                                            @include('admin.colors.swatch', ['color' => $productColor->color, 'sizeClass' => 'size-6'])
                                            {{ $productColor->nameEn() }}
                                        </span>
                                    </th>
                                    @foreach ($sizes as $size)
                                        @php
                                            $variant = $variantsByCell->get($productColor->id)?->get($size->id);
                                            $field = "cells[{$productColor->id}][{$size->id}]";
                                            $oldKey = "cells.{$productColor->id}.{$size->id}";
                                        @endphp
                                        <td x-show="sizes.includes({{ $size->id }})" class="px-2 py-2 align-top">
                                            <fieldset x-bind:disabled="! sizes.includes({{ $size->id }})" class="w-20 space-y-1">
                                                <input type="hidden" name="{{ $field }}[original_stock]" value="{{ $variant?->stock ?? 0 }}">
                                                <input
                                                    type="number"
                                                    name="{{ $field }}[stock]"
                                                    value="{{ old("{$oldKey}.stock", $variant?->stock ?? 0) }}"
                                                    min="0"
                                                    max="99999"
                                                    inputmode="numeric"
                                                    aria-label="Stock of {{ $productColor->nameEn() }} in size {{ $size->name_en }}"
                                                    @class([
                                                        'h-10 w-full rounded-lg border bg-white px-2 text-center tabular-nums focus:ring-2 focus:ring-maroon-100 focus:outline-none',
                                                        'border-danger-700' => $errors->has("{$oldKey}.stock"),
                                                        'border-sand-300 focus:border-maroon-700' => ! $errors->has("{$oldKey}.stock"),
                                                    ])
                                                >
                                                <label class="flex items-center justify-center gap-1 text-xs text-ink-500">
                                                    <input type="hidden" name="{{ $field }}[offered]" value="0">
                                                    <input
                                                        type="checkbox"
                                                        name="{{ $field }}[offered]"
                                                        value="1"
                                                        @checked(old("{$oldKey}.offered", $variant?->is_active ?? true))
                                                        class="size-3.5 accent-maroon-700"
                                                    >
                                                    Sell
                                                </label>
                                            </fieldset>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            <div class="sticky bottom-0 -mx-4 flex justify-end border-t border-sand-200 bg-sand-100/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0">
                <x-admin.button type="submit">Save sizes and stock</x-admin.button>
            </div>
        </form>

        @if ($offeredVariants->isNotEmpty())
            @php
                $variantErrors = collect([...$errors->get('variants'), ...$errors->get('variants.*')])->flatten()->unique()->values();
            @endphp

            <form id="skus-and-prices" method="POST" action="{{ route('admin.products.variants.update', $product) }}" class="scroll-mt-20" x-data="{ open: @js($variantErrors->isNotEmpty()) }">
                @csrf
                @method('PUT')

                <x-admin.card>
                    <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()">
                        <span>
                            <span class="block text-base font-semibold">SKUs and prices</span>
                            <span class="mt-1 block text-sm text-ink-500">Optional. SKUs are created for you. Set a price only for a size that costs more or less than {{ \App\Support\Money::format($product->price) }}.</span>
                        </span>
                        <x-icon name="chevron-down" class="size-5 shrink-0 text-ink-500 transition" x-bind:class="open && 'rotate-180'" />
                    </button>

                    <div x-show="open" x-cloak class="mt-5 space-y-4">
                        @if ($variantErrors->isNotEmpty())
                            <ul class="space-y-1 rounded-xl bg-danger-50 px-4 py-3 text-sm text-danger-700" role="alert">
                                @foreach ($variantErrors as $variantError)
                                    <li>{{ $variantError }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="-mx-5 overflow-x-auto sm:-mx-6">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-xs font-medium text-ink-500">
                                        <th scope="col" class="px-5 py-2 text-start sm:px-6">Color and size</th>
                                        <th scope="col" class="px-2 py-2 text-start">SKU</th>
                                        <th scope="col" class="px-5 py-2 text-start sm:px-6">Price (QAR)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-sand-200">
                                    @foreach ($offeredVariants as $variant)
                                        <tr>
                                            <td class="px-5 py-2 whitespace-nowrap sm:px-6">{{ $variant->productColor->nameEn() }} · {{ $variant->size->name_en }}</td>
                                            <td class="px-2 py-2">
                                                <input
                                                    type="text"
                                                    name="variants[{{ $variant->id }}][sku]"
                                                    value="{{ old("variants.{$variant->id}.sku", $variant->sku) }}"
                                                    maxlength="60"
                                                    required
                                                    aria-label="SKU"
                                                    class="h-10 w-56 rounded-lg border border-sand-300 bg-white px-2.5 font-mono text-xs uppercase focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none"
                                                >
                                            </td>
                                            <td class="px-5 py-2 sm:px-6">
                                                <input
                                                    type="text"
                                                    name="variants[{{ $variant->id }}][price]"
                                                    value="{{ old("variants.{$variant->id}.price", \App\Support\Money::toInput($variant->price)) }}"
                                                    inputmode="decimal"
                                                    placeholder="{{ \App\Support\Money::toInput($product->price) }}"
                                                    aria-label="Price in QAR"
                                                    class="h-10 w-28 rounded-lg border border-sand-300 bg-white px-2.5 tabular-nums focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none"
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end">
                            <x-admin.button type="submit" variant="secondary">Save SKUs and prices</x-admin.button>
                        </div>
                    </div>
                </x-admin.card>
            </form>
        @endif
    @endif
</x-admin.layout>
