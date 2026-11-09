{{-- Shared size guide fields. Expects $sizeGuide. --}}
@php
    $tableErrors = collect([
        ...$errors->get('columns'),
        ...$errors->get('columns.*'),
        ...$errors->get('rows'),
        ...$errors->get('rows.*'),
    ])->flatten()->unique()->values();

    $inputClasses = 'h-10 w-full rounded-lg border border-sand-300 bg-white px-2.5 text-sm focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none';
@endphp

<x-admin.card>
    <x-admin.input name="name" label="Name" :value="$sizeGuide->name" required maxlength="100" hint="Only you see this name, for example “Jelabiya by length”." />
</x-admin.card>

<x-admin.card title="Measurements" description="Enter every measurement in centimeters. Shoppers can switch to inches.">
    <div
        x-data="sizeGuideEditor(@js(old('columns', $sizeGuide->columns ?? [])), @js(old('rows', $sizeGuide->rows ?? [])))"
        class="space-y-4"
    >
        @if ($tableErrors->isNotEmpty())
            <ul class="space-y-1 rounded-xl bg-danger-50 px-4 py-3 text-sm text-danger-700" role="alert">
                @foreach ($tableErrors as $tableError)
                    <li>{{ $tableError }}</li>
                @endforeach
            </ul>
        @endif

        <div class="-mx-5 overflow-x-auto px-5 sm:-mx-6 sm:px-6">
            <table class="text-sm">
                <thead>
                    <tr>
                        <th scope="col" class="min-w-28 p-2 text-start align-bottom text-xs font-medium text-ink-500">Size</th>
                        <template x-for="(column, columnIndex) in columns" :key="columnIndex">
                            <th scope="col" class="min-w-36 p-2 align-bottom">
                                <div class="space-y-1.5">
                                    <input
                                        type="text"
                                        x-bind:name="`columns[${columnIndex}][en]`"
                                        x-model="column.en"
                                        placeholder="English, e.g. Length"
                                        aria-label="Measurement name in English"
                                        maxlength="40"
                                        required
                                        class="{{ $inputClasses }} font-medium"
                                    >
                                    <input
                                        type="text"
                                        x-bind:name="`columns[${columnIndex}][ar]`"
                                        x-model="column.ar"
                                        placeholder="بالعربية"
                                        aria-label="Measurement name in Arabic"
                                        maxlength="40"
                                        required
                                        dir="rtl"
                                        lang="ar"
                                        class="{{ $inputClasses }}"
                                    >
                                    <button
                                        type="button"
                                        x-show="columns.length > 1"
                                        x-on:click="removeColumn(columnIndex)"
                                        class="text-xs font-medium text-danger-700 hover:underline"
                                    >Remove</button>
                                </div>
                            </th>
                        </template>
                        <th scope="col" class="p-2 align-bottom">
                            <x-admin.button variant="secondary" size="sm" x-on:click="addColumn()" class="whitespace-nowrap">
                                <x-icon name="plus" class="size-4" />
                                Measurement
                            </x-admin.button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, rowIndex) in rows" :key="rowIndex">
                        <tr>
                            <td class="p-2">
                                <input
                                    type="text"
                                    x-bind:name="`rows[${rowIndex}][size]`"
                                    x-model="row.size"
                                    placeholder="e.g. 56"
                                    aria-label="Size"
                                    maxlength="20"
                                    required
                                    class="{{ $inputClasses }} font-medium"
                                >
                            </td>
                            <template x-for="(value, valueIndex) in row.values" :key="valueIndex">
                                <td class="p-2">
                                    <input
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="500"
                                        inputmode="decimal"
                                        x-bind:name="`rows[${rowIndex}][values][${valueIndex}]`"
                                        x-model="row.values[valueIndex]"
                                        aria-label="Measurement in centimeters"
                                        class="{{ $inputClasses }} tabular-nums"
                                    >
                                </td>
                            </template>
                            <td class="p-2">
                                <button
                                    type="button"
                                    x-show="rows.length > 1"
                                    x-on:click="removeRow(rowIndex)"
                                    class="inline-flex size-10 items-center justify-center rounded-lg text-danger-700 hover:bg-danger-50"
                                >
                                    <x-icon name="trash" class="size-4" />
                                    <span class="sr-only">Remove size</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <x-admin.button variant="secondary" size="sm" x-on:click="addRow()">
            <x-icon name="plus" class="size-4" />
            Add size
        </x-admin.button>
    </div>
</x-admin.card>

<x-admin.card title="Notes" description="Optional tips shown under the table, such as how to measure.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.textarea name="notes_en" label="English" :value="$sizeGuide->notes_en" />
        <x-admin.textarea name="notes_ar" label="Arabic" :value="$sizeGuide->notes_ar" dir="rtl" lang="ar" />
    </div>
</x-admin.card>
