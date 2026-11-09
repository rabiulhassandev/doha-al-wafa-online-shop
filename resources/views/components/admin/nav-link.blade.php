@props(['href', 'icon', 'active' => false])

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
</a>
