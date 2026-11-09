@php
    $promises = [
        ['icon' => 'cash', 'title' => __('Cash on delivery'), 'text' => __('Pay when your order arrives.')],
        ['icon' => 'truck', 'title' => __('Delivery across Qatar'), 'text' => __('Delivered to your door.')],
        ['icon' => 'whatsapp', 'title' => __('Help on WhatsApp'), 'text' => __('Ask us about sizes and colors.')],
    ];
@endphp

<ul {{ $attributes->merge(['class' => 'grid gap-px overflow-hidden rounded-2xl border border-sand-200 bg-sand-200 sm:grid-cols-3']) }}>
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
