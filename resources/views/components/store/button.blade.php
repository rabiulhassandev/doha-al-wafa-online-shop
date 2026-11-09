@props(['variant' => 'primary', 'size' => 'md', 'href' => null])

@php
    $classes = implode(' ', [
        'inline-flex items-center justify-center gap-2 rounded-full font-medium transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-maroon-700',
        'disabled:pointer-events-none disabled:opacity-50',
        match ($variant) {
            'secondary' => 'bg-ink-900 text-white hover:bg-ink-700',
            'outline' => 'border border-ink-900/15 bg-white text-ink-900 hover:border-ink-900/40',
            'light' => 'bg-white text-maroon-800 hover:bg-sand-100',
            'ghost' => 'text-ink-900 hover:bg-sand-100',
            default => 'bg-maroon-700 text-white hover:bg-maroon-800',
        },
        match ($size) {
            'sm' => 'h-10 px-4 text-sm',
            'lg' => 'h-14 px-8 text-base',
            default => 'h-12 px-6 text-sm',
        },
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
