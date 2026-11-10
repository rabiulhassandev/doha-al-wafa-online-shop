@inject('settings', 'App\Services\Settings')

{{-- The store's promises. The exchange promise comes from Settings and is left out when empty. --}}
@php
    $exchangePromise = $settings->get('exchange_promise_'.app()->getLocale());
    $promises = array_values(array_filter([
        ['icon' => 'cash', 'title' => __('Cash on delivery'), 'text' => __('Pay when your order arrives.')],
        ['icon' => 'truck', 'title' => __('Delivery across Qatar'), 'text' => __('Delivered to your door.')],
        filled($exchangePromise) ? ['icon' => 'shield', 'title' => __('Easy exchange'), 'text' => $exchangePromise] : null,
        ['icon' => 'whatsapp', 'title' => __('Help on WhatsApp'), 'text' => __('Ask us about sizes and colors.')],
    ]));
@endphp

<ul {{ $attributes->class([
    'grid gap-px overflow-hidden rounded-2xl border border-sand-200 bg-sand-200',
    'sm:grid-cols-3' => count($promises) === 3,
    'sm:grid-cols-2 lg:grid-cols-4' => count($promises) === 4,
]) }}>
    @foreach ($promises as $promise)
        <li class="flex items-start gap-3 bg-white p-5">
            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-maroon-50 text-maroon-700">
                <x-icon :name="$promise['icon']" class="size-5" />
            </span>
            <span>
                <span class="block text-sm font-semibold">{{ $promise['title'] }}</span>
                <span class="mt-0.5 block text-sm text-ink-500">{{ $promise['text'] }}</span>
            </span>
        </li>
    @endforeach
</ul>
