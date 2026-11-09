<x-admin.layout title="Dashboard">
    <x-admin.page-header :title="'Welcome, '.auth()->user()->name" description="Today at a glance. Reports arrive in a later phase." />

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['label' => 'Waiting for confirmation', 'value' => $orderStats['waiting'], 'href' => route('admin.orders.index', ['status' => 'pending_confirmation']), 'highlight' => $orderStats['waiting'] > 0],
            ['label' => 'Orders today', 'value' => $orderStats['todayCount'].' · '.\App\Support\Money::format($orderStats['todayTotal']), 'href' => route('admin.orders.index', ['from' => today()->toDateString()]), 'highlight' => false],
            ['label' => 'Out for delivery', 'value' => $orderStats['outForDelivery'], 'href' => route('admin.orders.index', ['status' => 'out_for_delivery']), 'highlight' => false],
        ] as $stat)
            <a href="{{ $stat['href'] }}" @class([
                'rounded-2xl border bg-white p-5 shadow-sm transition-colors hover:border-maroon-700',
                'border-maroon-700' => $stat['highlight'],
                'border-sand-200' => ! $stat['highlight'],
            ])>
                <p class="text-sm text-ink-500">{{ $stat['label'] }}</p>
                <p @class(['mt-1 text-2xl font-semibold tabular-nums', 'text-maroon-700' => $stat['highlight']])>{{ $stat['value'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-admin.card title="Store setup" description="Customers see these details in the store footer." class="lg:col-span-2">
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

        <x-admin.card title="Your storefront">
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
</x-admin.layout>
