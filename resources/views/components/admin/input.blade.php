@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'prefix' => null])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $describedBy = collect([$hint ? "{$id}-hint" : null, $hasError ? "{$id}-error" : null])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-900">{{ $label }}</label>

    <div @class([
        'mt-1.5 flex h-11 overflow-hidden rounded-lg border bg-white focus-within:ring-2 focus-within:ring-maroon-100',
        'border-danger-700 focus-within:border-danger-700' => $hasError,
        'border-sand-300 focus-within:border-maroon-700' => ! $hasError,
    ])>
        @if ($prefix)
            <span class="flex items-center border-e border-sand-200 bg-sand-50 px-3 text-sm text-ink-500">{{ $prefix }}</span>
        @endif

        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @unless ($type === 'password') value="{{ old($name, $value) }}" @endunless
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{ $attributes->except('id')->merge(['class' => 'w-full min-w-0 bg-transparent px-3 text-sm focus:outline-none']) }}
        >
    </div>

    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-ink-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
    @enderror
</div>
