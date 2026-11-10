{{-- The order lines and totals for emails. Expects $order with its items; $showSku adds SKUs for the team. --}}
@php
    use App\Support\Money;

    $locale = app()->getLocale();
    $showSku ??= false;
    $end = config("shop.locales.{$locale}.direction") === 'rtl' ? 'left' : 'right';
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 16px 0; border-top: 1px solid #eadfcf;">
    @foreach ($order->items as $item)
        <tr>
            <td width="64" style="padding: 12px 0; vertical-align: top;">
                @if ($item->imageUrl())
                    <img src="{{ $item->imageUrl() }}" alt="" width="56" height="70" style="display: block; width: 56px; height: 70px; border-radius: 8px; object-fit: cover; background-color: #f5efe6;">
                @endif
            </td>
            <td style="padding: 12px 8px; vertical-align: top; font-size: 14px;">
                <strong>{{ $item->translated('product_name') }}</strong><br>
                <span style="color: #6b625a;">
                    {{ $item->translated('color_name') }} · {{ __('Size :size', ['size' => $item->translated('size_name')]) }} · × {{ $item->quantity }}
                    @if ($showSku)
                        <br>SKU {{ $item->sku }}
                    @endif
                </span>
            </td>
            <td style="padding: 12px 0; vertical-align: top; text-align: {{ $end }}; font-size: 14px; white-space: nowrap;">
                {{ Money::format($item->line_total, $locale) }}
            </td>
        </tr>
    @endforeach
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; border-top: 1px solid #eadfcf;">
    <tr>
        <td style="padding: 8px 0 0; color: #3d3631;">{{ __('Subtotal') }}</td>
        <td style="padding: 8px 0 0; text-align: {{ $end }};">{{ Money::format($order->subtotal, $locale) }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0 0; color: #3d3631;">{{ __('Delivery') }} ({{ $order->translated('delivery_zone_name') }})</td>
        <td style="padding: 4px 0 0; text-align: {{ $end }};">{{ $order->delivery_fee === 0 ? __('Free') : Money::format($order->delivery_fee, $locale) }}</td>
    </tr>
    @if ($order->discount_total > 0)
        <tr>
            <td style="padding: 4px 0 0; color: #2f6b4f;">{{ __('Discount') }}@if ($order->coupon_code) ({{ $order->coupon_code }})@endif</td>
            <td style="padding: 4px 0 0; text-align: {{ $end }}; color: #2f6b4f;">−{{ Money::format($order->discount_total, $locale) }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding: 8px 0 0; font-size: 16px; font-weight: bold;">{{ __('Total') }}</td>
        <td style="padding: 8px 0 0; font-size: 16px; font-weight: bold; text-align: {{ $end }};">{{ Money::format($order->grand_total, $locale) }}</td>
    </tr>
</table>
