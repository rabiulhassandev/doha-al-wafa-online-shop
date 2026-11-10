@php
    use App\Support\Money;
    use App\Support\ReportPeriod;

    $groupingNames = ['day' => 'day', 'week' => 'week', 'month' => 'month'];
    $percent = fn (?float $rate): ?string => $rate === null ? null : number_format($rate * 100, $rate * 100 < 10 ? 1 : 0).'%';
@endphp

<x-admin.layout title="Reports">
    <x-admin.page-header title="Reports" :description="$period->label()" />

    <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3" x-data="{ range: @js($period->preset) }">
        <div class="w-full sm:w-48">
            <x-admin.select name="range" label="Period" :options="[...ReportPeriod::PRESETS, 'custom' => 'Choose dates']" :value="$period->preset" x-model="range" />
        </div>
        <div class="flex gap-3" x-show="range === 'custom'" x-cloak>
            <x-admin.input name="from" type="date" label="From" :value="$period->from->toDateString()" />
            <x-admin.input name="to" type="date" label="To" :value="$period->to->toDateString()" />
        </div>
        @if (count($period->groupingOptions()) > 1)
            <div class="w-40">
                <x-admin.select name="group" label="Chart" :options="$period->groupingOptions()" :value="$period->grouping" />
            </div>
        @endif
        <x-admin.button type="submit" variant="secondary">Show</x-admin.button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat label="Sales" :value="Money::format($summary['sales'])" :change="$changes['sales']" hint="Not counting cancelled or returned orders." />
        <x-admin.stat label="Orders" :value="number_format($summary['orders'])" :change="$changes['orders']" :hint="$summary['placed'].' placed, '.$summary['cancelled'].' cancelled.'" />
        <x-admin.stat label="Average order" :value="Money::format($summary['averageOrder'])" :change="$changes['averageOrder']" />
        <x-admin.stat label="Pieces sold" :value="number_format($summary['pieces'])" :change="$changes['pieces']" />
        <x-admin.stat label="Cash collected" :value="Money::format($summary['cashCollected'])" hint="Marked collected in these dates, less orders returned since." />
        <x-admin.stat label="Discounts given" :value="Money::format($summary['discounts'])" />
        <x-admin.stat
            label="Cancelled"
            :value="number_format($summary['cancelled'])"
            :hint="$summary['cancellationRate'] === null ? 'No orders placed.' : $percent($summary['cancellationRate']).' of orders placed.'"
        />
        <x-admin.stat
            label="Returned"
            :value="number_format($summary['returned'])"
            :hint="$summary['returnRate'] === null ? 'No orders delivered yet.' : $percent($summary['returnRate']).' of delivered orders.'"
        />
    </div>

    <x-admin.card :title="'Sales by '.$groupingNames[$period->grouping]" description="Hover or tab to a bar for its orders. Sales leave out cancelled and returned orders.">
        <x-slot:actions>
            <a href="{{ route('admin.reports.export', ['report' => 'sales', ...$period->query()]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-700 hover:underline">
                <x-icon name="download" class="size-4" />
                CSV
            </a>
        </x-slot:actions>

        <x-admin.column-chart :points="$chart" title="Sales" unit="QAR" empty-message="No sales in these dates." />
    </x-admin.card>

    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        @include('admin.reports.breakdown', [
            'title' => 'Orders by status',
            'description' => 'Every order placed in these dates, by where it is now.',
            'report' => 'statuses',
            'rows' => $statuses,
            'labelHeader' => 'Status',
            'columns' => ['orders' => ['Orders', 'count'], 'share' => ['Share', 'percent']],
            'barKey' => 'orders',
            'emptyMessage' => 'No orders placed in these dates.',
        ])

        @include('admin.reports.breakdown', [
            'title' => 'Where orders came from',
            'description' => 'Campaign tags, ad clicks and other sites, from the last link the customer opened.',
            'report' => 'sources',
            'rows' => $sources,
            'labelHeader' => 'Source',
            'columns' => ['orders' => ['Orders', 'count'], 'sales' => ['Sales', 'money']],
            'barKey' => 'sales',
            'emptyMessage' => 'No orders in these dates.',
        ])

        @include('admin.reports.breakdown', [
            'title' => 'Delivery areas',
            'report' => 'areas',
            'rows' => $areas,
            'labelHeader' => 'Area',
            'columns' => ['orders' => ['Orders', 'count'], 'sales' => ['Sales', 'money']],
            'barKey' => 'sales',
            'emptyMessage' => 'No orders in these dates.',
        ])

        @include('admin.reports.breakdown', [
            'title' => 'Campaigns',
            'description' => 'Orders that came through a link or ad with a campaign tag.',
            'report' => 'campaigns',
            'rows' => $campaigns,
            'labelHeader' => 'Campaign',
            'columns' => ['orders' => ['Orders', 'count'], 'sales' => ['Sales', 'money']],
            'barKey' => 'sales',
            'emptyMessage' => 'No orders with a campaign tag. Give short links a campaign to see them here.',
        ])

        @include('admin.reports.breakdown', [
            'title' => 'Discount codes',
            'report' => 'coupons',
            'rows' => $coupons,
            'labelHeader' => 'Code',
            'columns' => ['orders' => ['Orders', 'count'], 'discount' => ['Discount', 'money'], 'sales' => ['Sales', 'money']],
            'barKey' => 'sales',
            'emptyMessage' => 'No orders used a discount code.',
        ])

        @include('admin.reports.breakdown', [
            'title' => 'Products',
            'description' => 'Pieces sold and their value before discounts.',
            'report' => 'products',
            'rows' => $products,
            'labelHeader' => 'Product',
            'columns' => ['pieces' => ['Pieces', 'count'], 'sales' => ['Value', 'money']],
            'barKey' => 'pieces',
            'emptyMessage' => 'No pieces sold in these dates.',
        ])

        @include('admin.reports.breakdown', [
            'title' => 'Colors',
            'description' => 'Across all products.',
            'report' => 'colors',
            'rows' => $colors,
            'labelHeader' => 'Color',
            'columns' => ['pieces' => ['Pieces', 'count'], 'sales' => ['Value', 'money']],
            'barKey' => 'pieces',
            'emptyMessage' => 'No pieces sold in these dates.',
        ])

        @include('admin.reports.breakdown', [
            'title' => 'Sizes',
            'description' => 'Across all products.',
            'report' => 'sizes',
            'rows' => $sizes,
            'labelHeader' => 'Size',
            'columns' => ['pieces' => ['Pieces', 'count'], 'sales' => ['Value', 'money']],
            'barKey' => 'pieces',
            'emptyMessage' => 'No pieces sold in these dates.',
        ])
    </div>

    @include('admin.reports.breakdown', [
        'title' => 'Best sellers by color and size',
        'description' => 'What sold most, with the stock left today, to see what to restock.',
        'report' => 'best-sellers',
        'rows' => $bestSellers,
        'labelHeader' => 'Product, color and size',
        'columns' => ['pieces' => ['Pieces', 'count'], 'sales' => ['Value', 'money'], 'stock' => ['In stock now', 'count']],
        'barKey' => 'pieces',
        'emptyMessage' => 'No pieces sold in these dates.',
    ])

    <p class="text-xs text-ink-500">Dates follow Qatar time. Orders count from the moment they were placed; cancelled orders are left out, and sales also leave out returns.</p>
</x-admin.layout>
