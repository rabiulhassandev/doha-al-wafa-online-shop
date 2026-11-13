{{-- Sent to the customer when their order is placed. Expects $order and $confirmationUrl. --}}
@inject('settings', 'App\Services\Settings')

@php
    use App\Support\Money;

    $locale = app()->getLocale();
    $whatsApp = $settings->phone('whatsapp_number');
@endphp

<x-email.layout
    :title="__('Your order :number is placed', ['number' => $order->number])"
    :preheader="__('We will contact you to confirm it. You pay :total in cash on delivery.', ['total' => Money::format($order->grand_total, $locale)])"
    :footer="$whatsApp ? __('Questions? Message us on WhatsApp at :phone.', ['phone' => $whatsApp->formatted()]) : null"
>
    <h1 style="margin: 0 0 8px; font-size: 20px;">{{ __('Thank you, :name!', ['name' => $order->customer_name]) }}</h1>
    <p style="margin: 0 0 16px;">
        {{ __('We received your order :number. We will contact you on :phone to confirm it.', ['number' => $order->number, 'phone' => $order->formattedPhone()]) }}
    </p>

    <p style="margin: 0 0 16px; padding: 12px 16px; border-radius: 12px; background-color: #f8edf0; color: #63172a;">
        <strong>{{ __('You pay :total in cash when your order arrives.', ['total' => Money::format($order->grand_total, $locale)]) }}</strong><br>
        {{ $order->deliveryPromise() }}
    </p>

    @include('mail.orders.items', ['order' => $order])

    <p style="margin: 20px 0 4px; font-weight: bold;">{{ __('Delivery address') }}</p>
    <p style="margin: 0;">
        {{ $order->customer_name }}<br>
        {{ $order->translated('delivery_zone_name') }}<br>
        {{ $order->plateAddress() }}
        @if ($order->unit)
            <br>{{ $order->unit }}
        @endif
        @if ($order->landmark)
            <br>{{ $order->landmark }}
        @endif
    </p>

    <p style="margin: 24px 0 0;">
        <a href="{{ $confirmationUrl }}" style="display: inline-block; padding: 12px 24px; border-radius: 999px; background-color: #7a1e35; color: #ffffff; font-weight: bold; text-decoration: none;">{{ __('View your order') }}</a>
    </p>
</x-email.layout>
