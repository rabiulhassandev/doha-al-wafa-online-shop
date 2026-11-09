@props(['variant' => 'primary', 'size' => 'md', 'href' => null])

@php
    $classes = implode(' ', [
        'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-maroon-700',
        'disabled:pointer-events-none disabled:opacity-50',
        match ($variant) {
            'secondary' => 'border border-sand-300 bg-white text-ink-900 hover:bg-sand-50',
            'danger' => 'bg-danger-700 text-white hover:bg-danger-700/90',
            'ghost' => 'text-ink-700 hover:bg-sand-100',
            default => 'bg-maroon-700 text-white hover:bg-maroon-800',
        },
        match ($size) {
            'sm' => 'h-9 px-3 text-sm',
            default => 'h-11 px-4 text-sm',
        },
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
