@props(['href', 'icon', 'active' => false, 'count' => 0])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors',
        'bg-white/10 text-white' => $active,
        'text-sand-200 hover:bg-white/5 hover:text-white' => ! $active,
    ]) }}
>
    <x-icon :name="$icon" class="size-5" />
    {{ $slot }}
    @if ($count > 0)
        <span class="ms-auto inline-flex min-w-6 items-center justify-center rounded-full bg-maroon-700 px-1.5 text-xs font-semibold text-white tabular-nums">{{ $count }}</span>
    @endif
</a>
