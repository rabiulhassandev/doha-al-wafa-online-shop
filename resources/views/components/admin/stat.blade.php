{{--
    A headline number with an optional change against the period before.
    $change: ['text' => '+12% vs the 30 days before', 'direction' => 'up'|'down'|'flat', 'good' => true|false|null]
--}}
@props(['label', 'value', 'hint' => null, 'change' => null, 'href' => null, 'highlight' => false])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'block rounded-2xl border bg-white p-5 shadow-sm',
        'transition-colors hover:border-maroon-700' => $href,
        'border-maroon-700' => $highlight,
        'border-sand-200' => ! $highlight,
    ]) }}
>
    <p class="text-sm text-ink-500">{{ $label }}</p>
    <p @class(['mt-1 text-2xl font-semibold', 'text-maroon-700' => $highlight])>{{ $value }}</p>

    @if ($change)
        <p @class([
            'mt-1 flex items-center gap-1 text-xs font-medium',
            'text-success-700' => $change['good'] === true,
            'text-danger-700' => $change['good'] === false,
            'text-ink-500' => $change['good'] === null,
        ])>
            @if ($change['direction'] !== 'flat')
                <x-icon name="chevron-down" :class="$change['direction'] === 'up' ? 'size-3.5 rotate-180' : 'size-3.5'" />
            @endif
            {{ $change['text'] }}
        </p>
    @endif

    @if ($hint)
        <p class="mt-1 text-xs text-ink-500">{{ $hint }}</p>
    @endif
</{{ $tag }}>
