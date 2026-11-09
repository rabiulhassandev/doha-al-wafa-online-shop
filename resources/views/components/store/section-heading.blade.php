@props(['title', 'href' => null, 'linkLabel' => null])

<div {{ $attributes->merge(['class' => 'flex items-end justify-between gap-4']) }}>
    <h2 class="font-display text-2xl sm:text-3xl">{{ $title }}</h2>
    @if ($href)
        <a href="{{ $href }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-maroon-700 hover:underline">
            {{ $linkLabel ?? __('View all') }}
            <x-icon name="chevron-end" class="size-4" />
        </a>
    @endif
</div>
