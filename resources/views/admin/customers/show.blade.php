@php
    use App\Support\Money;
    use App\Support\QatarPhone;

    $phone = QatarPhone::tryParse($customer->phone);
@endphp

<x-admin.layout :title="$customer->name">
    <x-admin.page-header :title="$customer->name" :description="'Customer since '.$customer->created_at->format('j M Y')">
        <x-slot:actions>
            <x-admin.button :href="'tel:'.$customer->phone" variant="secondary">
                <x-icon name="phone" class="size-4" />
                Call
            </x-admin.button>
            @if ($phone)
                <x-admin.button :href="'https://wa.me/'.$phone->whatsAppNumber()" variant="secondary" target="_blank" rel="noopener">
                    <x-icon name="whatsapp" class="size-4" />
                    WhatsApp
                </x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($customer->is_blocked)
        <div class="rounded-xl border border-danger-700/20 bg-danger-50 px-4 py-3 text-sm text-danger-700" role="status">
            This phone number cannot order on the website. The team can still enter orders for it by hand.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[22rem_minmax(0,1fr)] lg:items-start">
        <x-admin.card title="Details">
            <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-5">
                @csrf
                @method('PUT')
                <div>
                    <p class="text-sm font-medium">Mobile number</p>
                    <p class="mt-1 text-sm tabular-nums" dir="ltr">{{ $phone?->formatted() ?? $customer->phone }}</p>
                </div>
                <x-admin.input name="name" label="Name" :value="$customer->name" maxlength="120" required />
                <x-admin.input name="email" type="email" label="Email (optional)" :value="$customer->email" />
                <x-admin.textarea name="notes" label="Team notes" :value="$customer->notes" rows="4" maxlength="2000" hint="Only the team sees this." />
                <x-admin.toggle
                    name="is_blocked"
                    label="Block this number from ordering on the website"
                    :checked="$customer->is_blocked"
                    hint="For numbers behind fake or refused orders."
                />
                <x-admin.button type="submit">Save customer</x-admin.button>
            </form>
        </x-admin.card>

        <div class="space-y-3">
            <h2 class="text-base font-semibold">Orders</h2>
            <x-admin.table>
                <x-slot:head>
                    <x-admin.th>Order</x-admin.th>
                    <x-admin.th class="text-end">Pieces</x-admin.th>
                    <x-admin.th class="text-end">Total</x-admin.th>
                    <x-admin.th>Status</x-admin.th>
                </x-slot:head>

                @forelse ($orders as $order)
                    <tr class="hover:bg-sand-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-maroon-700 hover:underline">{{ $order->number }}</a>
                            <p class="text-xs text-ink-500">{{ $order->placed_at->format('j M Y') }}</p>
                        </td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ (int) $order->items_sum_quantity }}</td>
                        <td class="px-4 py-3 text-end whitespace-nowrap tabular-nums">{{ Money::format($order->grand_total) }}</td>
                        <td class="px-4 py-3"><x-admin.badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-admin.badge></td>
                    </tr>
                @empty
                    <x-admin.empty-row colspan="4" message="No orders yet." />
                @endforelse
            </x-admin.table>

            {{ $orders->links() }}
        </div>
    </div>
</x-admin.layout>
