@props(['name', 'lines' => []])

{{--
    Find colors and sizes on sale by SKU or product name and list them with a quantity each.
    Submits {name}[n][variant_id] and {name}[n][quantity].
--}}
<div
    x-data="variantPicker({ searchUrl: @js(route('admin.variants.search')), inputName: @js($name), lines: @js($lines) })"
    {{ $attributes->merge(['class' => 'space-y-3']) }}
>
    <div class="relative" x-on:click.outside="results = []">
        <label for="{{ $name }}-search" class="block text-sm font-medium text-ink-900">Find a color and size</label>
        <input
            id="{{ $name }}-search"
            type="search"
            x-model="query"
            x-on:input.debounce.300ms="search()"
            x-on:keydown.escape="results = []"
            x-on:keydown.enter.prevent
            placeholder="SKU or product name"
            autocomplete="off"
            class="mt-1.5 block h-11 w-full rounded-lg border border-sand-300 bg-white px-3 text-sm focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none"
        >
        <ul x-show="results.length > 0" x-cloak class="absolute z-20 mt-1 max-h-80 w-full overflow-y-auto rounded-xl border border-sand-200 bg-white py-1 shadow-lg">
            <template x-for="variant in results" :key="variant.id">
                <li>
                    <button
                        type="button"
                        x-on:click="add(variant)"
                        x-bind:disabled="variant.stock < 1"
                        class="flex w-full items-center gap-3 px-3 py-2 text-start hover:bg-sand-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <img x-show="variant.image" x-bind:src="variant.image" alt="" class="h-10 w-8 shrink-0 rounded bg-sand-100 object-cover">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium" x-text="variant.label"></span>
                            <span class="block font-mono text-xs text-ink-500" x-text="variant.sku"></span>
                        </span>
                        <span class="shrink-0 text-end text-xs">
                            <span class="block font-medium" x-text="variant.price"></span>
                            <span x-bind:class="variant.stock < 1 ? 'text-danger-700' : 'text-ink-500'" x-text="variant.stock < 1 ? 'Out of stock' : variant.stock + ' in stock'"></span>
                        </span>
                    </button>
                </li>
            </template>
        </ul>
        <p x-show="searched && results.length === 0" x-cloak class="mt-1.5 text-xs text-ink-500">Nothing on sale matches that.</p>
    </div>

    <ul x-show="lines.length > 0" x-cloak class="divide-y divide-sand-200 rounded-xl border border-sand-200">
        <template x-for="(line, index) in lines" :key="line.id">
            <li class="flex flex-wrap items-center gap-3 px-3 py-2.5">
                <input type="hidden" x-bind:name="`${inputName}[${index}][variant_id]`" x-bind:value="line.id">
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium" x-text="line.label"></span>
                    <span class="block text-xs text-ink-500">
                        <span class="font-mono" x-text="line.sku"></span> · <span x-text="line.price"></span> · <span x-text="line.stock + ' in stock'"></span>
                    </span>
                </span>
                <label class="sr-only" x-bind:for="`${inputName}-quantity-${index}`">Quantity</label>
                <input
                    type="number"
                    min="1"
                    max="999"
                    x-bind:id="`${inputName}-quantity-${index}`"
                    x-bind:name="`${inputName}[${index}][quantity]`"
                    x-model.number="line.quantity"
                    class="h-9 w-20 rounded-lg border border-sand-300 bg-white px-2 text-sm tabular-nums focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none"
                >
                <button type="button" x-on:click="remove(index)" class="text-sm font-medium text-danger-700 hover:underline">Remove</button>
            </li>
        </template>
    </ul>
</div>
