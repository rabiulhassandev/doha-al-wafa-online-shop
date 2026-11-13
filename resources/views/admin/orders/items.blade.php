@php
    use App\Support\Money;
@endphp

<x-admin.layout :title="'Change items · '.$order->number">
    <x-admin.page-header
        :title="'Change items of order '.$order->number"
        description="Stock follows every change when you save. Lines already in the order keep the price they were sold at; added lines use today’s price."
    />

    <form method="POST" action="{{ route('admin.orders.items.update', $order) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.card title="Items in the order" description="Set a quantity to 0 to remove the line.">
            <ul class="divide-y divide-sand-200">
                @foreach ($order->items as $item)
                    <li class="flex flex-wrap items-center gap-4 py-3 first:pt-0">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $item->product_name_en }}</p>
                            <p class="text-sm text-ink-700">@if (filled($item->color_name_en)){{ $item->color_name_en }} · @endif Size {{ $item->size_name_en }} · {{ Money::format($item->unit_price) }} each</p>
                            <p class="font-mono text-xs text-ink-500">
                                {{ $item->sku }}
                                @if ($item->productVariant)
                                    · {{ $item->productVariant->stock }} more in stock
                                @else
                                    · no longer in the catalog
                                @endif
                            </p>
                        </div>
                        <label for="quantity-{{ $item->id }}" class="sr-only">Quantity of {{ $item->product_name_en }}</label>
                        <input
                            id="quantity-{{ $item->id }}"
                            name="quantities[{{ $item->id }}]"
                            type="number"
                            min="0"
                            max="999"
                            value="{{ old('quantities.'.$item->id, $item->quantity) }}"
                            class="h-10 w-20 rounded-lg border border-sand-300 bg-white px-2 text-sm tabular-nums focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none"
                        >
                    </li>
                @endforeach
            </ul>
            @error('quantities.*')
                <p class="mt-2 text-xs font-medium text-danger-700">{{ $message }}</p>
            @enderror
        </x-admin.card>

        <x-admin.card title="Add items">
            <x-admin.variant-picker name="additions" />
        </x-admin.card>

        <x-admin.card title="Delivery fee">
            <div class="max-w-xs">
                <x-admin.input name="delivery_fee" label="Delivery fee" prefix="QAR" inputmode="decimal" :value="$deliveryFee" required hint="The order total and the cash to collect are worked out again." />
            </div>
        </x-admin.card>

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.orders.show', $order)" variant="ghost">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
