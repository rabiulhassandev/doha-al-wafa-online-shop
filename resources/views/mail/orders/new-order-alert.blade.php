{{-- Sent to the store team for each new order, in English. Expects $order and $customerWhatsAppUrl. --}}
@php
    use App\Support\Money;
@endphp

<x-email.layout :title="'New order '.$order->number" :preheader="$order->customer_name.' · '.Money::format($order->grand_total).' · '.$order->delivery_zone_name_en">
    <h1 style="margin: 0 0 4px; font-size: 20px;">New order {{ $order->number }}</h1>
    <p style="margin: 0 0 16px; color: #6b625a;">Placed {{ $order->placed_at->format('D j M Y, g:i A') }} on the website, in {{ config("shop.locales.{$order->locale}.label") }}.</p>

    <p style="margin: 0 0 16px; padding: 12px 16px; border-radius: 12px; background-color: #f8edf0; color: #63172a;">
        <strong>Collect {{ Money::format($order->grand_total) }} in cash on delivery.</strong><br>
        Call or WhatsApp the customer to confirm the order before sending it.
    </p>

    <p style="margin: 0 0 4px; font-weight: bold;">Customer</p>
    <p style="margin: 0 0 16px;">
        {{ $order->customer_name }}<br>
        <a href="tel:{{ $order->customer_phone }}" style="color: #7a1e35;">{{ $order->formattedPhone() }}</a>
        · <a href="{{ $customerWhatsAppUrl }}" style="color: #7a1e35;">WhatsApp</a>
        @if ($order->customer_email)
            <br><a href="mailto:{{ $order->customer_email }}" style="color: #7a1e35;">{{ $order->customer_email }}</a>
        @endif
    </p>

    <p style="margin: 0 0 4px; font-weight: bold;">Delivery</p>
    <p style="margin: 0 0 16px;">
        {{ $order->delivery_zone_name_en }}<br>
        {{ $order->plateAddress() }}
        @if ($order->unit)
            <br>{{ $order->unit }}
        @endif
        @if ($order->landmark)
            <br>{{ $order->landmark }}
        @endif
        @if ($order->mapUrl())
            <br><a href="{{ $order->mapUrl() }}" style="color: #7a1e35;">Open the shared location in Maps</a>
        @endif
        @if ($order->preferred_delivery_date)
            <br><strong>Customer prefers delivery on {{ $order->preferred_delivery_date->format('l j F') }}.</strong>
        @else
            <br>{{ $order->deliveryPromise() }}
        @endif
    </p>

    @if ($order->customer_note)
        <p style="margin: 0 0 4px; font-weight: bold;">Note from the customer</p>
        <p style="margin: 0 0 16px; white-space: pre-line;">{{ $order->customer_note }}</p>
    @endif

    @include('mail.orders.items', ['order' => $order, 'showSku' => true])
</x-email.layout>
