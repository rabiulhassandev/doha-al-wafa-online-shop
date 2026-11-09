@props(['name', 'label', 'value' => null, 'hint' => null, 'rows' => 3])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-900">{{ $label }}</label>

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class([
            'mt-1.5 block w-full rounded-lg border bg-white px-3 py-2.5 text-sm focus:ring-2 focus:ring-maroon-100 focus:outline-none',
            'border-danger-700' => $hasError,
            'border-sand-300 focus:border-maroon-700' => ! $hasError,
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($hint)
        <p class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
    @enderror
</div>
