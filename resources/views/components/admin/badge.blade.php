@props(['color' => 'neutral'])

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
    match ($color) {
        'maroon' => 'bg-maroon-50 text-maroon-700',
        'success' => 'bg-success-50 text-success-700',
        'danger' => 'bg-danger-50 text-danger-700',
        default => 'bg-sand-100 text-ink-700',
    },
]) }}>{{ $slot }}</span>
