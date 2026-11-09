{{-- Shown after placing an order, through a signed link. Expects $order with its items, and $whatsAppUrl. --}}
@push('head')
    <meta name="robots" content="noindex">
@endpush

@php
    use App\Support\Money;

    $locale = app()->getLocale();
@endphp

<x-store.layout :title="__('Thank you for your order')">
    <div class="page-container py-8">
        <div x-data="orderPlaced" class="mx-auto max-w-3xl">
            <div class="text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-success-50 text-success-700">
                    <x-icon name="check" class="size-7" />
                </span>
                <h1 class="mt-4 font-display text-3xl sm:text-4xl">{{ __('Thank you, :name!', ['name' => $order->customer_name]) }}</h1>
                <p class="mt-2 text-ink-700">{{ __('Your order :number is placed.', ['number' => $order->number]) }}</p>
            </div>

            <div class="mt-6 rounded-2xl bg-maroon-50 p-5 text-maroon-900 sm:p-6">
                <h2 class="font-semibold">{{ __('What happens next') }}</h2>
                <ol class="mt-3 list-decimal space-y-2 ps-5 text-sm">
                    <li>{{ __('We call or WhatsApp you on :phone to confirm your order.', ['phone' => $order->formattedPhone()]) }}</li>
                    <li>{{ $order->deliveryPromise() }}</li>
                    <li>{{ __('You pay :total in cash when your order arrives.', ['total' => Money::format($order->grand_total, $locale)]) }}</li>
                </ol>
                @if ($whatsAppUrl)
                    <x-store.button :href="$whatsAppUrl" target="_blank" rel="noopener" class="mt-5 w-full sm:w-auto">
                        <x-icon name="whatsapp" class="size-5" />
                        {{ __('Confirm on WhatsApp') }}
                    </x-store.button>
                @endif
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <section class="rounded-2xl bg-white p-5">
                    <h2 class="text-sm font-semibold">{{ __('Delivery address') }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-ink-700">
                        {{ $order->customer_name }}<br>
                        <span dir="ltr">{{ $order->formattedPhone() }}</span><br>
                        {{ $order->translated('delivery_zone_name') }}<br>
                        {{ $order->plateAddress() }}
                        @if ($order->unit)
                            <br>{{ $order->unit }}
                        @endif
                        @if ($order->landmark)
                            <br>{{ $order->landmark }}
                        @endif
                    </p>
                </section>
                <section class="rounded-2xl bg-white p-5">
                    <h2 class="text-sm font-semibold">{{ __('Order details') }}</h2>
                    <dl class="mt-2 space-y-1 text-sm text-ink-700">
                        <div class="flex justify-between gap-4">
                            <dt>{{ __('Order number') }}</dt>
                            <dd class="font-medium text-ink-900" dir="ltr">{{ $order->number }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>{{ __('Placed on') }}</dt>
                            <dd>{{ $order->placed_at->locale($locale)->translatedFormat('j F Y') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>{{ __('Payment') }}</dt>
                            <dd>{{ $order->payment_method->label() }}</dd>
                        </div>
                    </dl>
                    @if ($order->customer_note)
                        <p class="mt-3 border-t border-sand-200 pt-3 text-sm whitespace-pre-line text-ink-700">{{ $order->customer_note }}</p>
                    @endif
                </section>
            </div>

            <section class="mt-5 rounded-2xl bg-white p-5" aria-labelledby="order-items">
                <h2 id="order-items" class="text-sm font-semibold">{{ trans_choice(':count item|:count items', $order->itemCount(), ['count' => $order->itemCount()]) }}</h2>
                <ul class="divide-y divide-sand-200">
                    @foreach ($order->items as $item)
                        <li class="flex gap-4 py-4">
                            @if ($item->imageUrl())
                                <img src="{{ $item->imageUrl() }}" alt="" class="h-25 w-20 shrink-0 rounded-xl bg-sand-100 object-cover" loading="lazy">
                            @else
                                <span class="block h-25 w-20 shrink-0 rounded-xl bg-sand-100"></span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-snug font-medium">{{ $item->translated('product_name') }}</p>
                                <p class="mt-1 flex items-center gap-1.5 text-xs text-ink-500">
                                    <span class="size-3 rounded-full ring-1 ring-ink-900/15" style="background-color: {{ $item->color_hex }}"></span>
                                    {{ $item->translated('color_name') }} · {{ __('Size :size', ['size' => $item->translated('size_name')]) }}
                                </p>
                                <p class="mt-1 text-xs text-ink-500">{{ __('Quantity: :count', ['count' => $item->quantity]) }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-semibold tabular-nums">{{ Money::format($item->line_total, $locale) }}</p>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 border-t border-sand-200 pt-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-700">{{ __('Subtotal') }}</dt>
                        <dd class="font-medium tabular-nums">{{ Money::format($order->subtotal, $locale) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-700">{{ __('Delivery') }} <span class="text-ink-500">({{ $order->translated('delivery_zone_name') }})</span></dt>
                        <dd class="font-medium tabular-nums">{{ $order->delivery_fee === 0 ? __('Free') : Money::format($order->delivery_fee, $locale) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-sand-200 pt-3 text-base">
                        <dt class="font-semibold">{{ __('To pay on delivery') }}</dt>
                        <dd class="font-semibold tabular-nums">{{ Money::format($order->grand_total, $locale) }}</dd>
                    </div>
                </dl>
            </section>

            <div class="mt-8 text-center">
                <x-store.button :href="route('shop')" variant="outline">{{ __('Continue shopping') }}</x-store.button>
            </div>
        </div>
    </div>
</x-store.layout>
