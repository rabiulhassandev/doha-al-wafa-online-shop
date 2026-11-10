{{--
    A one-series column chart: one bar per day, week or month, from a single baseline.
    Each bar shows its value and details on hover and keyboard focus; the tallest bar is labelled;
    "Show as a table" lists every value, so no number depends on hovering.

    $points: list of ['label' => 'Mon 3 Nov', 'axisLabel' => '3 Nov', 'value' => float, 'valueText' => 'QAR 1,250', 'detail' => '4 orders']
--}}
@props(['points', 'title', 'unit' => null, 'emptyMessage' => 'Nothing in this period yet.'])

@php
    use App\Support\ChartScale;

    $values = array_column($points, 'value');
    $largest = $values === [] ? 0 : max($values);
    $scaleMax = ChartScale::niceMax($largest);
    $ticks = [$scaleMax, $scaleMax / 2, 0];
    $largestIndex = $largest > 0 ? array_search($largest, $values, true) : null;
    $count = count($points);
    // About six dates along the bottom, whatever the number of bars.
    $labelEvery = max(1, (int) ceil($count / 6));
@endphp

<figure {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <figcaption class="sr-only">{{ $title }}</figcaption>

    @if ($largest <= 0)
        <p class="rounded-xl bg-sand-50 px-4 py-10 text-center text-sm text-ink-500">{{ $emptyMessage }}</p>
    @else
        <div class="flex gap-2" aria-hidden="false">
            <div class="relative h-48 w-12 shrink-0 text-end text-[11px] text-ink-500 tabular-nums" aria-hidden="true">
                @if ($unit)
                    <span class="absolute -top-5 end-0">{{ $unit }}</span>
                @endif
                @foreach ($ticks as $tick)
                    <span class="absolute end-0 -translate-y-1/2" style="top: {{ 100 - $tick / $scaleMax * 100 }}%">{{ ChartScale::tick($tick) }}</span>
                @endforeach
            </div>

            <div class="min-w-0 flex-1">
                <div class="relative h-48 border-b border-sand-300">
                    @foreach ([0, 50] as $gridline)
                        <div class="absolute inset-x-0 border-t border-sand-200" style="top: {{ $gridline }}%" aria-hidden="true"></div>
                    @endforeach

                    <ul class="absolute inset-0 flex items-end gap-0.5" role="list">
                        @foreach ($points as $index => $point)
                            @php
                                $height = $point['value'] > 0 ? max(1, $point['value'] / $scaleMax * 100) : 0;
                                $tooltipPosition = match (true) {
                                    $index < $count / 4 => 'start-0',
                                    $index >= $count * 3 / 4 => 'end-0',
                                    default => 'start-1/2 -translate-x-1/2',
                                };
                            @endphp
                            <li
                                class="group relative flex h-full min-w-0 flex-1 cursor-default items-end justify-center outline-none"
                                tabindex="0"
                                aria-label="{{ $point['label'] }}: {{ $point['valueText'] }}{{ filled($point['detail'] ?? null) ? ', '.$point['detail'] : '' }}"
                            >
                                @if ($index === $largestIndex)
                                    <span class="pointer-events-none absolute w-max text-[11px] font-medium text-ink-700 tabular-nums group-hover:invisible group-focus:invisible" style="bottom: calc({{ $height }}% + 4px)" aria-hidden="true">{{ $point['valueText'] }}</span>
                                @endif

                                <span
                                    class="block w-full max-w-6 rounded-t bg-maroon-600 transition-colors group-hover:bg-maroon-800 group-focus-visible:bg-maroon-800 group-focus-visible:ring-2 group-focus-visible:ring-maroon-100"
                                    style="height: {{ $height }}%"
                                    aria-hidden="true"
                                ></span>

                                <span
                                    class="pointer-events-none absolute z-10 hidden w-max max-w-48 rounded-lg bg-ink-900 px-2.5 py-1.5 text-start text-xs shadow-lg group-hover:block group-focus:block {{ $tooltipPosition }}"
                                    style="bottom: calc({{ $height }}% + 8px)"
                                    aria-hidden="true"
                                >
                                    <span class="block font-semibold text-white tabular-nums">{{ $point['valueText'] }}</span>
                                    <span class="block text-sand-200">{{ $point['label'] }}</span>
                                    @if (filled($point['detail'] ?? null))
                                        <span class="block text-sand-200">{{ $point['detail'] }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-1.5 flex gap-0.5 text-[11px] text-ink-500" aria-hidden="true">
                    @foreach ($points as $index => $point)
                        <span class="min-w-0 flex-1 overflow-visible text-center whitespace-nowrap">
                            @if ($index % $labelEvery === 0)
                                {{ $point['axisLabel'] }}
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <details class="text-sm">
        <summary class="cursor-pointer text-ink-500 hover:text-ink-900">Show as a table</summary>
        <div class="mt-2 max-h-72 overflow-y-auto rounded-xl border border-sand-200">
            <table class="min-w-full divide-y divide-sand-200 text-sm">
                <thead class="sticky top-0 bg-sand-50 text-xs text-ink-500">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-start font-medium">Period</th>
                        <th scope="col" class="px-3 py-2 text-end font-medium">{{ $title }}</th>
                        <th scope="col" class="px-3 py-2 text-end font-medium"><span class="sr-only">Details</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand-200">
                    @foreach ($points as $point)
                        <tr>
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ $point['label'] }}</td>
                            <td class="px-3 py-1.5 text-end whitespace-nowrap tabular-nums">{{ $point['valueText'] }}</td>
                            <td class="px-3 py-1.5 text-end whitespace-nowrap text-ink-500">{{ $point['detail'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
