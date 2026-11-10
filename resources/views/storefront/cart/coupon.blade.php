{{--
    Discount code box. Expects $summary (App\Support\CartSummary).
    Inside another form (checkout), pass $external = true: the inputs then belong to the
    "coupon-apply-form" and "coupon-remove-form" forms, which the page renders outside its own form.
--}}
@php
    $external ??= false;
@endphp

<div class="space-y-1.5">
    @if ($summary->coupon)
        <div class="flex items-center justify-between gap-3 rounded-xl bg-success-50 px-3 py-2.5 text-sm text-success-700">
            <span class="flex items-center gap-2">
                <x-icon name="tag" class="size-4" />
                <span><span class="font-semibold" dir="ltr">{{ $summary->coupon->code }}</span> · {{ $summary->coupon->benefit() }}</span>
            </span>
            @if ($external)
                <button type="submit" form="coupon-remove-form" class="text-xs font-medium underline underline-offset-4">{{ __('Remove') }}</button>
            @else
                <form method="POST" action="{{ route('cart.coupon.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-medium underline underline-offset-4">{{ __('Remove') }}</button>
                </form>
            @endif
        </div>
        @if ($summary->couponProblem)
            <p class="text-xs font-medium text-danger-700">{{ $summary->couponProblem }}</p>
        @endif
    @else
        @unless ($external)
            <form method="POST" action="{{ route('cart.coupon.store') }}" class="space-y-1.5">
                @csrf
        @endunless
                <label for="coupon-code" class="block text-sm font-medium">{{ __('Discount code') }}</label>
                <div class="flex gap-2">
                    <input
                        id="coupon-code"
                        name="code"
                        value="{{ old('code') }}"
                        @if ($external) form="coupon-apply-form" @endif
                        autocomplete="off"
                        autocapitalize="characters"
                        maxlength="30"
                        dir="ltr"
                        class="h-11 min-w-0 flex-1 rounded-xl border border-sand-300 bg-white px-3 text-base uppercase focus:border-maroon-700 focus:ring-2 focus:ring-maroon-100 focus:outline-none"
                    >
                    <x-store.button type="submit" variant="outline" :form="$external ? 'coupon-apply-form' : null">{{ __('Apply') }}</x-store.button>
                </div>
                @error('code', 'coupon')
                    <p class="text-xs font-medium text-danger-700" role="alert">{{ $message }}</p>
                @enderror
        @unless ($external)
            </form>
        @endunless
    @endif
</div>
