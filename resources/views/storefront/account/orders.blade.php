{{-- Orders placed while signed in. Expects $orders (paginated). --}}
@php
    use App\Support\Money;

    $locale = app()->getLocale();
@endphp

<x-store.account-layout :title="__('My orders')">
    @if ($orders->isEmpty())
        <div class="rounded-2xl border border-dashed border-sand-300 px-6 py-14 text-center">
            <x-icon name="bag" class="mx-auto size-8 text-ink-300" />
            <p class="mt-4 font-medium">{{ __('You have not ordered with this account yet.') }}</p>
            <p class="mt-1 text-sm text-ink-500">{{ __('Orders you place while signed in appear here. To follow an order placed without signing in, use Track your order.') }}</p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <x-store.button :href="route('shop')">{{ __('Browse all products') }}</x-store.button>
                <x-store.button :href="route('track.show')" variant="outline">{{ __('Track your order') }}</x-store.button>
            </div>
        </div>
    @else
        <ul class="divide-y divide-sand-200 rounded-2xl bg-white">
            @foreach ($orders as $order)
                <li>
                    <a href="{{ route('account.orders.show', $order) }}" class="flex flex-wrap items-center justify-between gap-3 p-4 hover:bg-sand-50 sm:p-5">
                        <span>
                            <span class="block font-semibold" dir="ltr">{{ $order->number }}</span>
                            <span class="mt-0.5 block text-sm text-ink-500">
                                {{ $order->placed_at->locale($locale)->translatedFormat('j F Y') }}
                                · {{ trans_choice(':count item|:count items', (int) $order->items_sum_quantity, ['count' => (int) $order->items_sum_quantity]) }}
                            </span>
                        </span>
                        <span class="flex items-center gap-4">
                            <span @class([
                                'rounded-full px-3 py-1 text-xs font-medium',
                                'bg-success-50 text-success-700' => $order->status === \App\Enums\OrderStatus::Delivered,
                                'bg-danger-50 text-danger-700' => in_array($order->status, [\App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Returned, \App\Enums\OrderStatus::DeliveryFailed], true),
                                'bg-maroon-50 text-maroon-700' => ! in_array($order->status, [\App\Enums\OrderStatus::Delivered, \App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Returned, \App\Enums\OrderStatus::DeliveryFailed], true),
                            ])>{{ $order->status->label() }}</span>
                            <span class="font-semibold tabular-nums">{{ Money::format($order->grand_total, $locale) }}</span>
                            <x-icon name="chevron-end" class="size-5 text-ink-300 rtl:-scale-x-100" />
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-store.account-layout>
