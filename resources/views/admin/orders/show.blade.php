@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
    use App\Support\Money;

    $nextStatuses = $order->status->nextStatuses();
    $languageLabel = config("shop.locales.{$order->locale}.label");
@endphp

<x-admin.layout :title="'Order '.$order->number">
    <x-admin.page-header :title="'Order '.$order->number" :description="'Placed '.$order->placed_at->format('D j M Y, H:i').' · '.$order->channel->label().' · '.$languageLabel">
        <x-slot:actions>
            @if ($order->status->allowsItemChanges())
                <x-admin.button :href="route('admin.orders.items.edit', $order)" variant="secondary">
                    <x-icon name="pencil" class="size-4" />
                    Change items
                </x-admin.button>
            @endif
            <x-admin.button :href="route('admin.orders.packing-slip', $order)" variant="secondary" target="_blank">Packing slip</x-admin.button>
            <x-admin.button :href="route('admin.orders.invoice', $order)" variant="secondary" target="_blank">Invoice</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="flex flex-wrap items-center gap-2">
        <x-admin.badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-admin.badge>
        <x-admin.badge :color="$order->payment_status->badgeColor()">{{ $order->payment_status->label() }}</x-admin.badge>
        <a href="{{ route('admin.orders.index') }}" class="ms-auto text-sm text-ink-500 hover:underline">All orders</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <div class="space-y-6">
            <x-admin.card :title="'Items ('.$order->itemCount().' pieces)'">
                <ul class="divide-y divide-sand-200">
                    @foreach ($order->items as $item)
                        <li class="flex gap-4 py-3 first:pt-0">
                            @if ($item->imageUrl())
                                <img src="{{ $item->imageUrl() }}" alt="" class="h-20 w-16 shrink-0 rounded-lg bg-sand-100 object-cover" loading="lazy">
                            @else
                                <span class="block h-20 w-16 shrink-0 rounded-lg bg-sand-100"></span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ $item->product_name_en }}</p>
                                <p class="mt-0.5 flex items-center gap-1.5 text-sm text-ink-700">
                                    <span class="size-3 rounded-full ring-1 ring-ink-900/15" style="background-color: {{ $item->color_hex }}"></span>
                                    {{ $item->color_name_en }} · Size {{ $item->size_name_en }}
                                </p>
                                <p class="font-mono text-xs text-ink-500">{{ $item->sku }}</p>
                                @if ($item->productVariant === null)
                                    <p class="mt-1 text-xs text-danger-700">This color and size was since deleted from the catalog.</p>
                                @endif
                            </div>
                            <div class="shrink-0 text-end text-sm">
                                <p class="font-medium tabular-nums">{{ Money::format($item->line_total) }}</p>
                                <p class="text-xs text-ink-500 tabular-nums">{{ $item->quantity }} × {{ Money::format($item->unit_price) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-2 space-y-1.5 border-t border-sand-200 pt-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-700">Subtotal</dt>
                        <dd class="tabular-nums">{{ Money::format($order->subtotal) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-700">Delivery ({{ $order->delivery_zone_name_en }})</dt>
                        <dd class="tabular-nums">{{ $order->delivery_fee === 0 ? 'Free' : Money::format($order->delivery_fee) }}</dd>
                    </div>
                    @if ($order->discount_total > 0)
                        <div class="flex justify-between gap-4 text-success-700">
                            <dt>Discount @if ($order->coupon_code)({{ $order->coupon_code }})@endif</dt>
                            <dd class="tabular-nums">−{{ Money::format($order->discount_total) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4 text-base font-semibold">
                        <dt>Total</dt>
                        <dd class="tabular-nums">{{ Money::format($order->grand_total) }}</dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card title="History">
                <ol class="space-y-5 border-s border-sand-200 ps-5">
                    @foreach ($timeline as $event)
                        <li class="relative">
                            <span class="absolute -start-6.5 top-1.5 size-3 rounded-full bg-maroon-700 ring-4 ring-white"></span>
                            <p class="text-sm font-medium">{{ $event['title'] }}</p>
                            <p class="text-xs text-ink-500">
                                {{ $event['at']->format('D j M, H:i') }}
                                @if ($event['by'])
                                    · {{ $event['by'] }}
                                @endif
                            </p>
                            @if ($event['note'])
                                <p class="mt-1 text-sm whitespace-pre-line text-ink-700">{{ $event['note'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Next step">
                @if ($nextStatuses === [])
                    <p class="text-sm text-ink-500">This order is {{ mb_strtolower($order->status->label()) }}. There is nothing more to do.</p>
                @else
                    <div class="space-y-2" x-data="{ open: @js(old('status')) }">
                        @foreach ($nextStatuses as $next)
                            <form method="POST" action="{{ route('admin.orders.status.update', $order) }}" class="rounded-xl border border-sand-200">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="{{ $next->value }}">
                                <button
                                    type="button"
                                    x-on:click="open = open === @js($next->value) ? null : @js($next->value)"
                                    x-bind:aria-expanded="open === @js($next->value)"
                                    @class([
                                        'flex h-11 w-full items-center justify-between gap-3 px-4 text-sm font-medium',
                                        'text-danger-700' => in_array($next, [OrderStatus::Cancelled, OrderStatus::DeliveryFailed], true),
                                    ])
                                >
                                    {{ $next->actionLabel() }}
                                    <x-icon name="chevron-down" class="size-4 text-ink-500" />
                                </button>
                                <div x-show="open === @js($next->value)" x-cloak class="space-y-3 border-t border-sand-200 px-4 py-3">
                                    <x-admin.textarea
                                        name="note"
                                        :id="'note-'.$next->value"
                                        :label="$next === OrderStatus::Cancelled ? 'Reason for cancelling' : 'Note for the history (optional)'"
                                        rows="2"
                                        maxlength="500"
                                    />
                                    @if ($next === OrderStatus::Delivered && $order->payment_status === PaymentStatus::Pending)
                                        <x-admin.toggle name="cash_collected" label="The driver collected the cash" :checked="true" />
                                    @endif
                                    @if ($next->restocks())
                                        <p class="text-xs text-ink-500">Every piece in this order goes back into stock.</p>
                                    @endif
                                    <x-admin.button type="submit" size="sm" :variant="in_array($next, [OrderStatus::Cancelled, OrderStatus::DeliveryFailed], true) ? 'danger' : 'primary'">
                                        {{ $next->actionLabel() }}
                                    </x-admin.button>
                                </div>
                            </form>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            <x-admin.card title="Customer">
                <p class="font-medium">{{ $order->customer_name }}</p>
                <p class="text-sm tabular-nums" dir="ltr">{{ $order->formattedPhone() }}</p>
                @if ($order->customer_email)
                    <a href="mailto:{{ $order->customer_email }}" class="text-sm text-maroon-700 hover:underline">{{ $order->customer_email }}</a>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-admin.button :href="'tel:'.$order->customer_phone" variant="secondary" size="sm">
                        <x-icon name="phone" class="size-4" />
                        Call
                    </x-admin.button>
                    <x-admin.button :href="'https://wa.me/'.ltrim($order->customer_phone, '+')" variant="secondary" size="sm" target="_blank" rel="noopener">
                        <x-icon name="whatsapp" class="size-4" />
                        WhatsApp
                    </x-admin.button>
                </div>

                @if ($whatsAppMessages !== [])
                    <p class="mt-4 text-xs font-medium text-ink-500">Ready-made WhatsApp messages, in {{ $languageLabel }}</p>
                    <ul class="mt-1.5 space-y-1">
                        @foreach ($whatsAppMessages as $message)
                            <li>
                                <a href="{{ $message['url'] }}" target="_blank" rel="noopener" class="text-sm font-medium text-maroon-700 hover:underline">{{ $message['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-sand-200 pt-4 text-sm">
                    <a href="{{ route('admin.customers.show', $order->customer) }}" class="font-medium hover:underline">
                        {{ $order->customer->orders_count === 1 ? 'First order' : $order->customer->orders_count.' orders' }} from this customer
                    </a>
                    @if ($order->customer->is_blocked)
                        <x-admin.badge color="danger">Blocked from ordering online</x-admin.badge>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card title="Delivery">
                <p class="text-sm leading-relaxed">
                    <span class="font-medium">{{ $order->delivery_zone_name_en }}</span><br>
                    {{ $order->plateAddress() }}
                    @if ($order->unit)
                        <br>{{ $order->unit }}
                    @endif
                    @if ($order->landmark)
                        <br>{{ $order->landmark }}
                    @endif
                </p>
                @if ($order->mapUrl())
                    <a href="{{ $order->mapUrl() }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-maroon-700 hover:underline">
                        <x-icon name="map-pin" class="size-4" />
                        Open the shared location in Maps
                    </a>
                @endif
                <p class="mt-3 text-sm text-ink-700">
                    @if ($order->preferred_delivery_date)
                        <span class="font-medium">Customer prefers {{ $order->preferred_delivery_date->format('l j F') }}.</span>
                    @else
                        {{ $order->deliveryPromise() }}
                    @endif
                </p>
                @if ($order->customer_note)
                    <div class="mt-3 rounded-lg bg-sand-50 p-3 text-sm">
                        <p class="text-xs font-medium text-ink-500">Note from the customer</p>
                        <p class="mt-1 whitespace-pre-line">{{ $order->customer_note }}</p>
                    </div>
                @endif
            </x-admin.card>

            <x-admin.card title="Payment">
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-700">Method</dt>
                        <dd>{{ $order->payment_method->label() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-700">{{ $order->payment_status === PaymentStatus::Paid ? 'Collected' : 'To collect' }}</dt>
                        <dd class="font-semibold tabular-nums">{{ Money::format($order->grand_total) }}</dd>
                    </div>
                    @foreach ($order->payments->whereNotNull('paid_at') as $payment)
                        <div class="flex justify-between gap-4 text-ink-500">
                            <dt>Collected on</dt>
                            <dd>{{ $payment->paid_at->format('j M, H:i') }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($canCollectCash)
                    <form method="POST" action="{{ route('admin.orders.payment.update', $order) }}" class="mt-4">
                        @csrf
                        @method('PUT')
                        <x-admin.button type="submit" variant="secondary" size="sm">
                            <x-icon name="cash" class="size-4" />
                            Mark cash collected
                        </x-admin.button>
                    </form>
                @endif
            </x-admin.card>

            <x-admin.card title="Team note" description="Only the team sees this.">
                <form method="POST" action="{{ route('admin.orders.note.update', $order) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <x-admin.textarea name="admin_note" label="Note" :value="$order->admin_note" rows="3" maxlength="2000" />
                    <x-admin.button type="submit" variant="secondary" size="sm">Save note</x-admin.button>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-admin.layout>
