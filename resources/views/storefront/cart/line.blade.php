{{-- One bag line with quantity buttons. Expects $line (App\Support\CartLine) and optionally $readOnly. --}}
@php
    use App\Enums\CartLineStatus;
    use App\Support\Money;

    $readOnly ??= false;
    $locale = app()->getLocale();
    $variant = $line->variant;
    $productColor = $variant->productColor;
    $image = $productColor->images->first();
    $url = route('products.show', ['product' => $variant->product, 'color' => $productColor->color->slug, 'size' => $variant->size->slug]);
@endphp

<li class="flex gap-4 py-4">
    <a href="{{ $url }}" class="shrink-0">
        @if ($image)
            <img src="{{ $image->url(400) }}" alt="" class="h-25 w-20 rounded-xl bg-sand-100 object-cover" loading="lazy">
        @else
            <span class="block h-25 w-20 rounded-xl bg-sand-100"></span>
        @endif
    </a>

    <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ $url }}" class="block text-sm leading-snug font-medium hover:underline">{{ $variant->product->translated('name') }}</a>
                <p class="mt-1 flex items-center gap-1.5 text-xs text-ink-500">
                    <span class="size-3 rounded-full ring-1 ring-ink-900/15" style="background-color: {{ $productColor->color->hex }}"></span>
                    {{ $productColor->displayName() }} · {{ __('Size :size', ['size' => $variant->size->translated('name')]) }}
                </p>
            </div>
            <p class="shrink-0 text-end text-sm">
                <span class="font-semibold">{{ Money::format($line->unitPrice * max(1, $line->quantity), $locale) }}</span>
                @if ($line->compareAtPrice)
                    <span class="block text-xs text-ink-500 line-through">{{ Money::format($line->compareAtPrice * max(1, $line->quantity), $locale) }}</span>
                @endif
            </p>
        </div>

        @if ($line->status === CartLineStatus::Reduced)
            <p class="mt-2 text-xs font-medium text-danger-700">{{ __('Only :count left, so we lowered the quantity.', ['count' => $line->quantity]) }}</p>
        @elseif ($line->status === CartLineStatus::Unavailable)
            <p class="mt-2 text-xs font-medium text-danger-700">{{ __('Sold out. Remove it to continue.') }}</p>
        @endif

        @if ($readOnly)
            <p class="mt-2 text-xs text-ink-500">{{ __('Quantity: :count', ['count' => $line->quantity]) }}</p>
        @elseif ($line->item)
            <div class="mt-3 flex items-center justify-between">
                @if ($line->isOrderable())
                    <div class="inline-flex items-center rounded-full border border-sand-300 bg-white">
                        <form method="POST" action="{{ route('cart.items.update', $line->item) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="quantity" value="{{ $line->quantity - 1 }}">
                            <button type="submit" class="inline-flex size-9 items-center justify-center rounded-full text-lg hover:bg-sand-100">
                                <span aria-hidden="true">−</span>
                                <span class="sr-only">{{ __('One less') }}</span>
                            </button>
                        </form>
                        <span class="min-w-8 text-center text-sm font-medium tabular-nums" aria-live="polite">{{ $line->quantity }}</span>
                        <form method="POST" action="{{ route('cart.items.update', $line->item) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="quantity" value="{{ $line->quantity + 1 }}">
                            <button type="submit" class="inline-flex size-9 items-center justify-center rounded-full text-lg hover:bg-sand-100 disabled:opacity-30" @disabled($line->quantity >= $line->maxQuantity)>
                                <span aria-hidden="true">+</span>
                                <span class="sr-only">{{ __('One more') }}</span>
                            </button>
                        </form>
                    </div>
                @else
                    <span></span>
                @endif

                <form method="POST" action="{{ route('cart.items.destroy', $line->item) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-medium text-ink-500 underline underline-offset-4 hover:text-ink-900">{{ __('Remove') }}</button>
                </form>
            </div>
        @endif
    </div>
</li>
