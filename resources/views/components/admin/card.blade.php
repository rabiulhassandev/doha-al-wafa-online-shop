@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-sand-200 bg-white p-5 shadow-sm sm:p-6']) }}>
    @if ($title)
        <header class="mb-5">
            <h2 class="text-base font-semibold">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-sm text-ink-500">{{ $description }}</p>
            @endif
        </header>
    @endif

    {{ $slot }}
</section>
