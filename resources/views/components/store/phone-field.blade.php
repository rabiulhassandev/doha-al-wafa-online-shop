@props(['name' => 'phone', 'label', 'hint' => null, 'value' => null])

{{-- A Qatar phone number input with a fixed +974 prefix, its hint and error message. Other attributes go on the input. --}}
@php
    $hasError = $errors->has($name);
    $describedBy = collect([$hint ? "{$name}-hint" : null, $hasError ? "{$name}-error" : null])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <div @class([
        'mt-1.5 flex h-12 overflow-hidden rounded-xl border bg-white focus-within:ring-2',
        'border-danger-700 focus-within:ring-danger-50' => $hasError,
        'border-sand-300 focus-within:border-maroon-700 focus-within:ring-maroon-100' => ! $hasError,
    ]) dir="ltr">
        <span class="flex items-center border-e border-sand-200 bg-sand-50 px-3 text-sm text-ink-500">+974</span>
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="tel"
            inputmode="tel"
            autocomplete="tel-national"
            placeholder="5555 1234"
            value="{{ old($name, $value) }}"
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{ $attributes->merge(['maxlength' => '20', 'class' => 'w-full min-w-0 bg-transparent px-4 text-base focus:outline-none']) }}
        >
    </div>
    @if ($hint)
        <p id="{{ $name }}-hint" class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
    @enderror
</div>
