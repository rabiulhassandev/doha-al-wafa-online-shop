@php
    use App\Support\Money;

    $setupDone = collect($setupTasks)->every(fn (array $task): bool => $task['done']);
    $topProductPieces = $topProducts === [] ? 0 : max(array_column($topProducts, 'pieces'));
@endphp

<x-admin.layout title="Dashboard">
    <x-admin.page-header :title="'Welcome, '.auth()->user()->name" description="Today at a glance.">
        @can('manage-store')
            <x-slot:actions>
                <x-admin.button :href="route('admin.reports.index')" variant="secondary" size="sm">
                    <x-icon name="chart" class="size-4" />
                    Open reports
                </x-admin.button>
            </x-slot:actions>
        @endcan
    </x-admin.page-header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat
            label="Waiting for confirmation"
            :value="number_format($orderStats['waiting'])"
            :href="route('admin.orders.index', ['status' => 'pending_confirmation'])"
            :highlight="$orderStats['waiting'] > 0"
        />
        <x-admin.stat
            label="Orders today"
            :value="$owner ? $orderStats['todayCount'].' · '.Money::format($orderStats['todayTotal']) : number_format($orderStats['todayCount'])"
            :href="route('admin.orders.index', ['from' => today()->toDateString()])"
        />
        <x-admin.stat
            label="Out for delivery"
            :value="number_format($orderStats['outForDelivery'])"
            :href="route('admin.orders.index', ['status' => 'out_for_delivery'])"
        />
        <x-admin.stat
            label="Sizes running low"
            :value="number_format($orderStats['lowStock'])"
            :href="route('admin.inventory.index', ['stock' => 'low'])"
        />
    </div>

    @if ($owner)
        <x-admin.card title="Sales, last 14 days" description="Leaving out cancelled and returned orders.">
            <dl class="mb-6 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    'Sales this month' => Money::format($owner['month']['sales']),
                    'Cash collected this month' => Money::format($owner['month']['cashCollected']),
                    'Average order this month' => Money::format($owner['month']['averageOrder']),
                ] as $figureLabel => $figure)
                    <div class="rounded-xl bg-sand-50 px-4 py-3">
                        <dt class="text-xs text-ink-500">{{ $figureLabel }}</dt>
                        <dd class="mt-0.5 text-lg font-semibold">{{ $figure }}</dd>
                    </div>
                @endforeach
            </dl>

            <x-admin.column-chart :points="$owner['chart']" title="Sales" unit="QAR" empty-message="No sales in the last 14 days yet." />
        </x-admin.card>
    @endif

    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        <x-admin.card title="Best sellers, last 30 days" description="Pieces sold, leaving out cancelled and returned orders.">
            @if ($topProducts === [])
                <p class="rounded-xl bg-sand-50 px-4 py-6 text-center text-sm text-ink-500">Nothing sold in the last 30 days yet.</p>
            @else
                <ol class="space-y-3 text-sm">
                    @foreach ($topProducts as $topProduct)
                        <li class="grid grid-cols-[minmax(0,1fr)_6rem_3rem] items-center gap-3">
                            <span class="truncate">{{ $topProduct['label'] }}</span>
                            <x-admin.rank-bar :value="$topProduct['pieces']" :max="$topProductPieces" class="min-w-0" />
                            <span class="text-end tabular-nums">{{ number_format($topProduct['pieces']) }}</span>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-5 border-t border-sand-200 pt-4">
                    <p class="text-xs font-medium text-ink-500">Top colors</p>
                    <ul class="mt-2 flex flex-wrap gap-2 text-sm">
                        @foreach ($topColors as $topColor)
                            <li class="inline-flex items-center gap-1.5 rounded-full bg-sand-50 px-3 py-1">
                                <span class="size-3 rounded-full ring-1 ring-ink-900/10" style="background-color: {{ $topColor['hex'] }}" aria-hidden="true"></span>
                                {{ $topColor['label'] }}
                                <span class="text-ink-500 tabular-nums">{{ number_format($topColor['pieces']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-admin.card>

        <x-admin.card title="Running low">
            <x-slot:actions>
                <a href="{{ route('admin.inventory.index', ['stock' => 'low']) }}" class="text-sm font-medium text-ink-700 hover:underline">See all</a>
            </x-slot:actions>

            @if ($lowStockVariants->isEmpty())
                <p class="rounded-xl bg-sand-50 px-4 py-6 text-center text-sm text-ink-500">No sizes are running low.</p>
            @else
                <ul class="divide-y divide-sand-200 text-sm">
                    @foreach ($lowStockVariants as $variant)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{{ $variant->product->name_en }}</span>
                                <span class="flex items-center gap-1.5 text-xs text-ink-500">
                                    @include('admin.colors.swatch', ['color' => $variant->productColor->color, 'sizeClass' => 'size-3.5'])
                                    {{ $variant->productColor->nameEn() }} · {{ $variant->size->name_en }}
                                </span>
                            </span>
                            <span class="flex shrink-0 items-center gap-3">
                                <x-admin.badge color="maroon">{{ $variant->stock }} left</x-admin.badge>
                                <a href="{{ route('admin.inventory.adjust', $variant) }}" class="font-medium text-ink-900 hover:underline">Adjust</a>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        @if ($owner)
            <x-admin.card title="Where orders came from, last 30 days" class="lg:col-span-2">
                @php
                    $topSourceOrders = $owner['sources'] === [] ? 0 : max(array_column($owner['sources'], 'orders'));
                @endphp
                @if ($owner['sources'] === [])
                    <p class="rounded-xl bg-sand-50 px-4 py-6 text-center text-sm text-ink-500">No orders in the last 30 days yet.</p>
                @else
                    <ol class="space-y-3 text-sm">
                        @foreach ($owner['sources'] as $source)
                            <li class="grid grid-cols-[minmax(0,1fr)_6rem_5.5rem] items-center gap-3">
                                <span class="truncate">{{ $source['label'] }}</span>
                                <x-admin.rank-bar :value="$source['orders']" :max="$topSourceOrders" class="min-w-0" />
                                <span class="text-end tabular-nums">{{ $source['orders'] }} {{ str('order')->plural($source['orders']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-admin.card>
        @endif

        <x-admin.card title="Your storefront" :class="$owner ? '' : 'lg:col-span-3'">
            <div class="space-y-2">
                @foreach (config('shop.locales') as $code => $locale)
                    <a href="{{ route('home', ['locale' => $code]) }}" target="_blank" class="flex h-11 items-center justify-between rounded-lg border border-sand-200 px-3 text-sm hover:bg-sand-50">
                        <span lang="{{ $code }}">{{ $locale['label'] }}</span>
                        <x-icon name="external" class="size-4 text-ink-500" />
                    </a>
                @endforeach
            </div>
        </x-admin.card>
    </div>

    @unless ($setupDone)
        <x-admin.card title="Store setup" description="Customers see these details in the store footer.">
            <ul class="divide-y divide-sand-200">
                @foreach ($setupTasks as $task)
                    <li class="flex items-center gap-3 py-3 text-sm">
                        @if ($task['done'])
                            <span class="inline-flex size-6 items-center justify-center rounded-full bg-success-50 text-success-700">
                                <x-icon name="check" class="size-4" />
                            </span>
                            <span class="text-ink-500 line-through">{{ $task['label'] }}</span>
                        @else
                            <span class="inline-flex size-6 items-center justify-center rounded-full border border-sand-300 text-sand-300">
                                <x-icon name="circle" class="size-3" />
                            </span>
                            <span>{{ $task['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @can('manage-store')
                <div class="mt-5">
                    <x-admin.button :href="route('admin.settings.edit')" variant="secondary" size="sm">
                        <x-icon name="settings" class="size-4" />
                        Open settings
                    </x-admin.button>
                </div>
            @endcan
        </x-admin.card>
    @endunless
</x-admin.layout>
