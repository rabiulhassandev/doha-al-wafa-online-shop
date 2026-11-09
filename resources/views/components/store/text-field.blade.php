@props(['name', 'label', 'value' => null, 'hint' => null, 'optional' => false])

{{-- A labelled text input with its hint and error message. Other attributes go on the input. --}}
@php
    $hasError = $errors->has($name);
    $describedBy = collect([$hint ? "{$name}-hint" : null, $hasError ? "{$name}-error" : null])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $name }}" class="block text-sm font-medium">
        {{ $label }}
        @if ($optional)
            <span class="font-normal text-ink-500">{{ __('(optional)') }}</span>
        @endif
    </label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->merge(['type' => 'text'])->class([
            'mt-1.5 block h-12 w-full rounded-xl border bg-white px-4 text-base focus:ring-2 focus:outline-none',
            'border-danger-700 focus:border-danger-700 focus:ring-danger-50' => $hasError,
            'border-sand-300 focus:border-maroon-700 focus:ring-maroon-100' => ! $hasError,
        ]) }}
    >
    @if ($hint)
        <p id="{{ $name }}-hint" class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
    @enderror
</div>
