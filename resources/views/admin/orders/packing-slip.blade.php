{{-- For packing and delivering an order: where it goes, what is in it and the cash to collect. Expects $order with its items. --}}
@inject('settings', 'App\Services\Settings')

@php
    use App\Enums\PaymentStatus;
    use App\Support\Money;

    $locale = app()->getLocale();
@endphp

<x-admin.print-layout :title="__('Packing slip').' '.$order->number" :back-url="route('admin.orders.show', $order)">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-sand-200 pb-5">
        <div>
            <p class="text-sm text-ink-500">{{ __('Packing slip') }} · {{ $settings->get('store_name') }}</p>
            <h1 class="mt-1 text-4xl font-semibold tracking-tight" dir="ltr">{{ $order->number }}</h1>
        </div>
        <p class="text-sm text-ink-500">{{ __('Placed on') }}: {{ $order->placed_at->locale($locale)->translatedFormat('j F Y') }}</p>
    </header>

    <div class="mt-6 grid gap-6 sm:grid-cols-[minmax(0,1fr)_16rem]">
        <section>
            <h2 class="text-xs font-semibold tracking-wider text-ink-500 uppercase">{{ __('Deliver to') }}</h2>
            <p class="mt-2 text-xl font-semibold">{{ $order->customer_name }}</p>
            <p class="text-xl tabular-nums" dir="ltr">{{ $order->formattedPhone() }}</p>
            <p class="mt-2 leading-relaxed">
                <span class="font-medium">{{ $order->translated('delivery_zone_name') }}</span><br>
                {{ $order->plateAddress() }}
                @if ($order->unit)
                    <br>{{ $order->unit }}
                @endif
                @if ($order->landmark)
                    <br>{{ $order->landmark }}
                @endif
            </p>
            <p class="mt-3 text-sm text-ink-700">{{ $order->deliveryPromise() }}</p>
        </section>

        <section class="self-start rounded-2xl border-2 border-ink-900 p-4 text-center">
            @if ($order->payment_status === PaymentStatus::Paid)
                <p class="text-lg font-semibold">{{ __('Paid. Nothing to collect.') }}</p>
            @else
                <p class="text-sm font-medium">{{ __('Collect in cash') }}</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">{{ Money::format($order->grand_total, $locale) }}</p>
            @endif
        </section>
    </div>

    <table class="mt-8 w-full text-sm">
        <thead>
            <tr class="border-b border-sand-300 text-xs text-ink-500">
                <th class="w-10 py-2"><span class="sr-only">{{ __('Packed') }}</span></th>
                <th class="py-2 text-start font-medium">{{ __('Qty') }}</th>
                <th class="py-2 text-start font-medium">{{ __('Item') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-sand-200">
            @foreach ($order->items as $item)
                <tr>
                    <td class="py-3 align-top"><span class="block size-5 rounded border-2 border-ink-900"></span></td>
                    <td class="py-3 align-top text-lg font-semibold tabular-nums">{{ $item->quantity }} ×</td>
                    <td class="py-3">
                        <p class="font-medium">{{ $item->translated('product_name') }}</p>
                        <p class="text-ink-700">{{ $item->translated('color_name') }} · {{ __('Size :size', ['size' => $item->translated('size_name')]) }}</p>
                        <p class="font-mono text-xs text-ink-500" dir="ltr">{{ $item->sku }}</p>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($order->customer_note)
        <section class="mt-8 rounded-xl bg-sand-50 p-4 print:border print:border-sand-300">
            <h2 class="text-xs font-semibold tracking-wider text-ink-500 uppercase">{{ __('Note from the customer') }}</h2>
            <p class="mt-1 whitespace-pre-line">{{ $order->customer_note }}</p>
        </section>
    @endif
</x-admin.print-layout>
