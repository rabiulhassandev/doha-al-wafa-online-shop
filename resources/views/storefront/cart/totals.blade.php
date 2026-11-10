{{-- Free delivery progress and order totals. Expects $summary (App\Support\CartSummary). --}}
@php
    use App\Support\Money;

    $locale = app()->getLocale();
    $amountToFreeDelivery = $summary->amountToFreeDelivery();
    $threshold = $summary->freeDeliveryThreshold();
    $deliveryFee = $summary->deliveryFee();
@endphp

<div class="space-y-4">
    @if ($amountToFreeDelivery !== null && $threshold > 0)
        <div>
            <p class="text-sm">
                @if ($amountToFreeDelivery === 0)
                    <span class="font-medium text-success-700">{{ __('Your order gets free delivery.') }}</span>
                @else
                    {{ __('Add :amount more for free delivery.', ['amount' => Money::format($amountToFreeDelivery, $locale)]) }}
                @endif
            </p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-sand-200" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(100, (int) round($summary->subtotal() / $threshold * 100)) }}">
                <div class="h-full rounded-full bg-success-700" style="width: {{ min(100, round($summary->subtotal() / $threshold * 100)) }}%"></div>
            </div>
        </div>
    @endif

    <dl class="space-y-2 text-sm">
        <div class="flex justify-between gap-4">
            <dt class="text-ink-700">{{ __('Subtotal') }}</dt>
            <dd class="font-medium tabular-nums">{{ Money::format($summary->subtotal(), $locale) }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-ink-700">
                {{ __('Delivery') }}
                @if ($summary->deliveryZone)
                    <span class="text-ink-500">({{ $summary->deliveryZone->translated('name') }})</span>
                @endif
            </dt>
            <dd class="font-medium tabular-nums">
                @if ($deliveryFee === null)
                    <span class="font-normal text-ink-500">{{ __('Choose your area') }}</span>
                @elseif ($deliveryFee === 0)
                    <span class="text-success-700">{{ __('Free') }}</span>
                @else
                    {{ Money::format($deliveryFee, $locale) }}
                @endif
            </dd>
        </div>
        @if ($summary->coupon && $summary->couponProblem === null)
            <div class="flex justify-between gap-4 text-success-700">
                <dt>{{ __('Discount') }} <span dir="ltr">({{ $summary->coupon->code }})</span></dt>
                <dd class="font-medium tabular-nums">{{ $summary->discount() > 0 ? '−'.Money::format($summary->discount(), $locale) : $summary->coupon->benefit() }}</dd>
            </div>
        @endif
        <div class="flex justify-between gap-4 border-t border-sand-200 pt-3 text-base">
            <dt class="font-semibold">{{ $deliveryFee === null ? __('Total before delivery') : __('Total') }}</dt>
            <dd class="font-semibold tabular-nums">{{ Money::format($summary->total(), $locale) }}</dd>
        </div>
    </dl>

    <p class="flex items-center gap-2 text-xs text-ink-500">
        <x-icon name="cash" class="size-4" />
        {{ __('You pay in cash when your order arrives.') }}
    </p>
</div>
