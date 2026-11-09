@php
    use App\Enums\OrderChannel;
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
    use App\Support\Money;

    $currentTab = $filters->status?->value ?? '';
    $tabs = ['' => ['All', $statusCounts->sum(), $currentTab === '']];

    foreach (OrderStatus::cases() as $status) {
        $tabs[$status->value] = [$status->label(), $statusCounts->get($status->value, 0), $currentTab === $status->value];
    }
@endphp

<x-admin.layout title="Orders">
    <x-admin.page-header title="Orders" description="Newest first. Confirm new orders with the customer before sending them out.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.orders.export', $filters->query())" variant="secondary">Download CSV</x-admin.button>
            <x-admin.button :href="route('admin.orders.create')">
                <x-icon name="plus" class="size-4" />
                Enter an order
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <nav class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Order status">
        <ul class="flex gap-2">
            @foreach ($tabs as $value => [$label, $count, $isCurrent])
                <li>
                    <a
                        href="{{ route('admin.orders.index', $filters->query(['status' => $value === '' ? null : $value])) }}"
                        @if ($isCurrent) aria-current="page" @endif
                        @class([
                            'inline-flex h-9 items-center gap-2 rounded-full border px-3 text-sm font-medium whitespace-nowrap',
                            'border-ink-900 bg-ink-900 text-white' => $isCurrent,
                            'border-sand-300 bg-white text-ink-700 hover:border-ink-900/40' => ! $isCurrent,
                        ])
                    >
                        {{ $label }}
                        <span @class(['tabular-nums', 'text-white/70' => $isCurrent, 'text-ink-500' => ! $isCurrent])>{{ $count }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <form method="GET" action="{{ route('admin.orders.index') }}" class="grid gap-3 rounded-2xl border border-sand-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-[minmax(0,1.5fr)_repeat(3,minmax(0,1fr))]">
        @if ($filters->status)
            <input type="hidden" name="status" value="{{ $filters->status->value }}">
        @endif
        <x-admin.input name="search" type="search" label="Search" :value="$filters->search" placeholder="Order number, name or phone" />
        <x-admin.select name="payment" label="Payment" :options="['' => 'Any payment'] + PaymentStatus::options()" :value="$filters->paymentStatus?->value" />
        <x-admin.select name="channel" label="From" :options="['' => 'Anywhere'] + collect(OrderChannel::cases())->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()])->all()" :value="$filters->channel?->value" />
        <x-admin.select name="area" label="Delivery area" :options="['' => 'All areas'] + $deliveryZones->all()" :value="$filters->deliveryZoneId" />
        <x-admin.input name="from" type="date" label="Placed from" :value="$filters->from?->toDateString()" />
        <x-admin.input name="to" type="date" label="Placed until" :value="$filters->to?->toDateString()" />
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-2">
            <x-admin.button type="submit" variant="secondary">Filter</x-admin.button>
            @if ($filters->narrowsList())
                <x-admin.button :href="route('admin.orders.index', $filters->status ? ['status' => $filters->status->value] : [])" variant="ghost">Clear filters</x-admin.button>
            @endif
        </div>
    </form>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Order</x-admin.th>
            <x-admin.th>Customer</x-admin.th>
            <x-admin.th>Area</x-admin.th>
            <x-admin.th class="text-end">Pieces</x-admin.th>
            <x-admin.th class="text-end">Total</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th>Payment</x-admin.th>
        </x-slot:head>

        @forelse ($orders as $order)
            <tr class="hover:bg-sand-50">
                <td class="px-4 py-3 whitespace-nowrap">
                    <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-maroon-700 hover:underline">{{ $order->number }}</a>
                    <p class="text-xs text-ink-500">{{ $order->placed_at->format('j M, H:i') }}@if ($order->channel !== OrderChannel::Web) · {{ $order->channel->label() }}@endif</p>
                </td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $order->customer_name }}</p>
                    <p class="text-xs text-ink-500 tabular-nums" dir="ltr">{{ $order->formattedPhone() }}</p>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">{{ $order->delivery_zone_name_en }}</td>
                <td class="px-4 py-3 text-end tabular-nums">{{ (int) $order->items_sum_quantity }}</td>
                <td class="px-4 py-3 text-end font-medium whitespace-nowrap tabular-nums">{{ Money::format($order->grand_total) }}</td>
                <td class="px-4 py-3"><x-admin.badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-admin.badge></td>
                <td class="px-4 py-3"><x-admin.badge :color="$order->payment_status->badgeColor()">{{ $order->payment_status->label() }}</x-admin.badge></td>
            </tr>
        @empty
            <x-admin.empty-row colspan="7" :message="$filters->narrowsList() || $filters->status ? 'No orders match these filters.' : 'No orders yet. Orders placed on the website appear here.'" />
        @endforelse
    </x-admin.table>

    {{ $orders->links() }}
</x-admin.layout>
