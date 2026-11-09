{{-- The customer's invoice, in the language they ordered in. Expects $order with its items. --}}
@inject('settings', 'App\Services\Settings')

@php
    use App\Enums\PaymentStatus;
    use App\Support\Money;

    $locale = app()->getLocale();
    $whatsApp = $settings->phone('whatsapp_number');
    $contactPhone = $settings->phone('contact_phone');
    $paidAt = $order->payments->whereNotNull('paid_at')->last()?->paid_at;
@endphp

<x-admin.print-layout :title="__('Invoice').' '.$order->number" :back-url="route('admin.orders.show', $order)">
    <header class="flex flex-wrap items-start justify-between gap-6 border-b border-sand-200 pb-6">
        <div>
            <p class="font-brand text-2xl text-maroon-700">{{ $settings->get('store_name') }}</p>
            <p class="mt-1 text-sm text-ink-500">
                @if ($whatsApp)
                    <span dir="ltr">{{ $whatsApp->formatted() }}</span>
                @elseif ($contactPhone)
                    <span dir="ltr">{{ $contactPhone->formatted() }}</span>
                @endif
                @if ($settings->get('contact_email'))
                    <br>{{ $settings->get('contact_email') }}
                @endif
            </p>
        </div>
        <div class="text-end">
            <h1 class="font-display text-3xl">{{ __('Invoice') }}</h1>
            <p class="mt-1 text-sm"><span class="text-ink-500">{{ __('Order number') }}:</span> <span dir="ltr">{{ $order->number }}</span></p>
            <p class="text-sm"><span class="text-ink-500">{{ __('Placed on') }}:</span> {{ $order->placed_at->locale($locale)->translatedFormat('j F Y') }}</p>
        </div>
    </header>

    <section class="mt-6">
        <h2 class="text-xs font-semibold tracking-wider text-ink-500 uppercase">{{ __('Bill to') }}</h2>
        <p class="mt-2 text-sm leading-relaxed">
            <span class="font-medium">{{ $order->customer_name }}</span><br>
            <span dir="ltr">{{ $order->formattedPhone() }}</span><br>
            {{ $order->translated('delivery_zone_name') }} · {{ $order->plateAddress() }}
            @if ($order->unit)
                <br>{{ $order->unit }}
            @endif
        </p>
    </section>

    <table class="mt-8 w-full text-sm">
        <thead>
            <tr class="border-b border-sand-300 text-start text-xs text-ink-500">
                <th class="py-2 text-start font-medium">{{ __('Item') }}</th>
                <th class="py-2 text-end font-medium">{{ __('Qty') }}</th>
                <th class="py-2 text-end font-medium">{{ __('Price') }}</th>
                <th class="py-2 text-end font-medium">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-sand-200">
            @foreach ($order->items as $item)
                <tr>
                    <td class="py-3">
                        <p class="font-medium">{{ $item->translated('product_name') }}</p>
                        <p class="text-xs text-ink-500">{{ $item->translated('color_name') }} · {{ __('Size :size', ['size' => $item->translated('size_name')]) }} · <span dir="ltr">{{ $item->sku }}</span></p>
                    </td>
                    <td class="py-3 text-end tabular-nums">{{ $item->quantity }}</td>
                    <td class="py-3 text-end whitespace-nowrap tabular-nums">{{ Money::format($item->unit_price, $locale) }}</td>
                    <td class="py-3 text-end whitespace-nowrap tabular-nums">{{ Money::format($item->line_total, $locale) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <dl class="ms-auto mt-4 w-full max-w-xs space-y-1.5 border-t border-sand-300 pt-3 text-sm">
        <div class="flex justify-between gap-4">
            <dt class="text-ink-700">{{ __('Subtotal') }}</dt>
            <dd class="tabular-nums">{{ Money::format($order->subtotal, $locale) }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-ink-700">{{ __('Delivery') }}</dt>
            <dd class="tabular-nums">{{ $order->delivery_fee === 0 ? __('Free') : Money::format($order->delivery_fee, $locale) }}</dd>
        </div>
        <div class="flex justify-between gap-4 text-base font-semibold">
            <dt>{{ __('Total') }}</dt>
            <dd class="tabular-nums">{{ Money::format($order->grand_total, $locale) }}</dd>
        </div>
    </dl>

    <p class="mt-8 rounded-xl border border-sand-300 px-4 py-3 text-sm">
        @if ($order->payment_status === PaymentStatus::Paid && $paidAt)
            {{ __('Paid in cash on :date.', ['date' => $paidAt->locale($locale)->translatedFormat('j F Y')]) }}
        @else
            <span class="font-semibold">{{ __('To pay in cash on delivery: :total', ['total' => Money::format($order->grand_total, $locale)]) }}</span>
        @endif
    </p>

    <p class="mt-10 text-center text-sm text-ink-500">{{ __('Thank you for shopping with us.') }}</p>
</x-admin.print-layout>
