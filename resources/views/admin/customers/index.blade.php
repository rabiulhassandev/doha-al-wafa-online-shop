@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
@endphp

<x-admin.layout title="Customers">
    <x-admin.page-header title="Customers" description="Added from orders, one per mobile number. Most recent buyers first." />

    <form method="GET" action="{{ route('admin.customers.index') }}" class="grid gap-3 rounded-2xl border border-sand-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-end">
        <x-admin.input name="search" type="search" label="Search" :value="$search" placeholder="Name or phone" />
        <label class="flex h-11 items-center gap-2 text-sm font-medium">
            <input type="checkbox" name="blocked" value="1" @checked($blockedOnly) class="size-4 accent-maroon-700">
            Blocked only
        </label>
        <div class="flex gap-2">
            <x-admin.button type="submit" variant="secondary">Filter</x-admin.button>
            @if ($search !== '' || $blockedOnly)
                <x-admin.button :href="route('admin.customers.index')" variant="ghost">Clear</x-admin.button>
            @endif
        </div>
    </form>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Customer</x-admin.th>
            <x-admin.th class="text-end">Orders</x-admin.th>
            <x-admin.th class="text-end">Spent</x-admin.th>
            <x-admin.th>Last order</x-admin.th>
        </x-slot:head>

        @forelse ($customers as $customer)
            <tr class="hover:bg-sand-50">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium hover:underline">{{ $customer->name }}</a>
                    @if ($customer->is_blocked)
                        <x-admin.badge color="danger" class="ms-2">Blocked</x-admin.badge>
                    @endif
                    <p class="text-xs text-ink-500 tabular-nums" dir="ltr">{{ \App\Support\QatarPhone::tryParse($customer->phone)?->formatted() ?? $customer->phone }}</p>
                </td>
                <td class="px-4 py-3 text-end tabular-nums">{{ $customer->orders_count }}</td>
                <td class="px-4 py-3 text-end whitespace-nowrap tabular-nums">{{ Money::format((int) $customer->spent) }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">{{ $customer->orders_max_placed_at ? Carbon::parse($customer->orders_max_placed_at)->format('j M Y') : '—' }}</td>
            </tr>
        @empty
            <x-admin.empty-row colspan="4" :message="$search !== '' || $blockedOnly ? 'No customers match.' : 'No customers yet. They are added when someone orders.'" />
        @endforelse
    </x-admin.table>

    {{ $customers->links() }}
</x-admin.layout>
