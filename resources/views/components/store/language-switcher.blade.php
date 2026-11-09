<div {{ $attributes->merge(['class' => 'flex items-center']) }}>
    @foreach ($alternatives as $code => $alternative)
        <a
            href="{{ $alternative['url'] }}"
            hreflang="{{ $code }}"
            lang="{{ $code }}"
            class="inline-flex h-11 items-center gap-2 rounded-full px-3 text-sm font-medium text-ink-700 hover:bg-sand-100"
        >
            <x-icon name="globe" class="size-4" />
            {{ $alternative['label'] }}
        </a>
    @endforeach
</div>
