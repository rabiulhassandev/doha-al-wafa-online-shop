@props(['name', 'label', 'options' => [], 'value' => null, 'hint' => null])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $selected = (string) old($name, $value);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-900">{{ $label }}</label>

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class([
            'mt-1.5 block h-11 w-full rounded-lg border bg-white px-3 text-sm focus:ring-2 focus:ring-maroon-100 focus:outline-none',
            'border-danger-700' => $hasError,
            'border-sand-300 focus:border-maroon-700' => ! $hasError,
        ]) }}
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($hint)
        <p class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
    @enderror
</div>
