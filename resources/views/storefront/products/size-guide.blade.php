{{-- Size guide sheet. Values are stored in centimeters; inches are worked out here. Expects $sizeGuide. --}}
@php
    $locale = app()->getLocale();
@endphp

<div x-cloak x-show="sizeGuideOpen" x-on:keydown.escape.window="sizeGuideOpen = false" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="size-guide-title">
    <div x-show="sizeGuideOpen" x-transition.opacity class="absolute inset-0 bg-ink-900/40" x-on:click="sizeGuideOpen = false"></div>
    <div
        x-show="sizeGuideOpen"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
        x-transition:enter-end="translate-y-0 sm:opacity-100"
        class="absolute inset-x-0 bottom-0 max-h-[85dvh] overflow-y-auto rounded-t-3xl bg-sand-50 p-5 sm:inset-auto sm:top-1/2 sm:left-1/2 sm:w-full sm:max-w-lg sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-3xl"
    >
        <div class="flex items-center justify-between gap-3">
            <h2 id="size-guide-title" class="text-lg font-semibold">{{ __('Size guide') }}</h2>
            <button type="button" x-on:click="sizeGuideOpen = false" class="-me-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand-100">
                <x-icon name="close" class="size-6" />
                <span class="sr-only">{{ __('Close size guide') }}</span>
            </button>
        </div>

        <div class="mt-3 inline-flex rounded-full border border-sand-300 bg-white p-0.5 text-sm" role="group" aria-label="{{ __('Units') }}">
            <button type="button" x-on:click="sizeUnit = 'cm'" x-bind:class="sizeUnit === 'cm' && 'bg-ink-900 text-white'" class="rounded-full px-4 py-1.5">{{ __('cm') }}</button>
            <button type="button" x-on:click="sizeUnit = 'in'" x-bind:class="sizeUnit === 'in' && 'bg-ink-900 text-white'" class="rounded-full px-4 py-1.5">{{ __('inches') }}</button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-center text-sm">
                <thead>
                    <tr class="border-b border-sand-300">
                        <th scope="col" class="py-2 pe-3 text-start font-semibold">{{ __('Size') }}</th>
                        @foreach ($sizeGuide->columns as $column)
                            <th scope="col" class="px-2 py-2 font-semibold">{{ $column[$locale] ?? $column['en'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand-200">
                    @foreach ($sizeGuide->rows as $row)
                        <tr>
                            <th scope="row" class="py-2 pe-3 text-start font-medium">{{ $row['size'] }}</th>
                            @foreach ($row['values'] as $value)
                                <td class="px-2 py-2 tabular-nums">
                                    @if ($value === null)
                                        –
                                    @else
                                        <span x-show="sizeUnit === 'cm'">{{ rtrim(rtrim(number_format((float) $value, 1), '0'), '.') }}</span>
                                        <span x-show="sizeUnit === 'in'" style="display: none">{{ rtrim(rtrim(number_format((float) $value / 2.54, 1), '0'), '.') }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($sizeGuide->translated('notes'))
            <p class="mt-4 text-sm text-ink-700">{{ $sizeGuide->translated('notes') }}</p>
        @endif
    </div>
</div>
