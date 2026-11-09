@props(['product', 'current'])

@php
    $tabs = [
        'details' => ['label' => 'Details', 'url' => route('admin.products.edit', $product)],
        'colors' => ['label' => 'Colors & photos', 'url' => route('admin.products.colors.index', $product)],
        'stock' => ['label' => 'Sizes & stock', 'url' => route('admin.products.stock.edit', $product)],
    ];
    $setupIssues = $product->relationLoaded('colors') && $product->relationLoaded('variants') ? $product->setupIssues() : [];
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('admin.products.index') }}" class="text-sm text-ink-500 hover:underline">← Products</a>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $product->name_en }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-ink-500">
                <span>{{ $product->code }}</span>
                <x-admin.badge :color="$product->status->badgeColor()">{{ $product->status->label() }}</x-admin.badge>
            </p>
        </div>
    </div>

    <nav class="-mx-4 overflow-x-auto border-b border-sand-200 px-4 sm:mx-0 sm:px-0" aria-label="Product sections">
        <ul class="flex gap-6 text-sm font-medium whitespace-nowrap">
            @foreach ($tabs as $key => $tab)
                <li>
                    <a
                        href="{{ $tab['url'] }}"
                        @if ($key === $current) aria-current="page" @endif
                        @class([
                            '-mb-px inline-flex h-11 items-center border-b-2',
                            'border-maroon-700 text-maroon-700' => $key === $current,
                            'border-transparent text-ink-500 hover:text-ink-900' => $key !== $current,
                        ])
                    >{{ $tab['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    @if ($setupIssues !== [])
        <div class="rounded-xl border border-gold-500/40 bg-gold-300/20 px-4 py-3 text-sm text-ink-900">
            <p class="font-medium">Shoppers can't order this product yet:</p>
            <ul class="mt-1 list-disc ps-5 text-ink-700">
                @foreach ($setupIssues as $setupIssue)
                    <li>{{ $setupIssue }}</li>
                @endforeach
            </ul>
        </div>
    @elseif ($product->status !== \App\Enums\ProductStatus::Active)
        <div class="rounded-xl border border-sand-300 bg-white px-4 py-3 text-sm text-ink-700">
            This product is ready. Set its status to <strong>Active</strong> on the Details tab to show it in the store.
        </div>
    @endif
</div>
