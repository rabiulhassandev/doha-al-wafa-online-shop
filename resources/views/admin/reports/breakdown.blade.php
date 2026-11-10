{{--
    One breakdown as a ranked table: a name, a thin bar for the main figure, then the figures.
    Expects $title, $report (CSV key), $rows, $labelHeader, $columns (key => [header, 'money'|'count'|'percent']),
    $barKey, $emptyMessage and $period; optional $description and $shown (rows shown, the rest are in the CSV).
--}}
@php
    use App\Http\Controllers\Admin\ReportController;
    use App\Support\Money;

    $shown ??= ReportController::ROWS_SHOWN;
    $description ??= null;
    $visibleRows = array_slice($rows, 0, $shown);
    $barMax = $rows === [] ? 0 : max(array_column($rows, $barKey));
@endphp

<x-admin.card :title="$title" :description="$description">
    @if ($rows !== [])
        <x-slot:actions>
            <a href="{{ route('admin.reports.export', ['report' => $report, ...$period->query()]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-700 hover:underline">
                <x-icon name="download" class="size-4" />
                CSV
            </a>
        </x-slot:actions>
    @endif

    @if ($rows === [])
        <p class="rounded-xl bg-sand-50 px-4 py-6 text-center text-sm text-ink-500">{{ $emptyMessage }}</p>
    @else
        <div class="-mx-5 overflow-x-auto sm:-mx-6">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-ink-500">
                    <tr>
                        <th scope="col" class="px-5 pb-2 text-start font-medium sm:ps-6">{{ $labelHeader }}</th>
                        <th scope="col" class="w-1/4 pb-2"><span class="sr-only">Compared</span></th>
                        @foreach ($columns as [$header])
                            <th scope="col" class="px-3 pb-2 text-end font-medium whitespace-nowrap last:pe-5 sm:last:pe-6">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand-100">
                    @foreach ($visibleRows as $row)
                        <tr>
                            <td class="px-5 py-2 sm:ps-6">
                                <span class="flex items-center gap-2">
                                    @isset($row['hex'])
                                        <span class="size-3.5 shrink-0 rounded-full ring-1 ring-ink-900/10" style="background-color: {{ $row['hex'] }}" aria-hidden="true"></span>
                                    @endisset
                                    @isset($row['status'])
                                        <x-admin.badge :color="$row['status']->badgeColor()">{{ $row['label'] }}</x-admin.badge>
                                    @else
                                        <span class="min-w-0 break-words">{{ $row['label'] }}</span>
                                    @endisset
                                </span>
                            </td>
                            <td class="py-2">
                                <x-admin.rank-bar :value="$row[$barKey]" :max="$barMax" />
                            </td>
                            @foreach ($columns as $key => [$header, $format])
                                <td class="px-3 py-2 text-end whitespace-nowrap tabular-nums last:pe-5 sm:last:pe-6">
                                    {{ match ($format) {
                                        'money' => Money::format($row[$key]),
                                        'percent' => number_format($row[$key] * 100, $row[$key] * 100 < 10 ? 1 : 0).'%',
                                        default => $row[$key] === null ? '—' : number_format($row[$key]),
                                    } }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (count($rows) > $shown)
            <p class="mt-3 text-xs text-ink-500">Showing the top {{ $shown }} of {{ count($rows) }}. The CSV has them all.</p>
        @endif
    @endif
</x-admin.card>
