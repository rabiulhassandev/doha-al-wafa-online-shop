@props(['items'])

<nav aria-label="{{ __('Breadcrumb') }}" {{ $attributes->merge(['class' => 'text-xs text-ink-500']) }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as [$label, $url])
            <li class="flex items-center gap-1.5">
                @unless ($loop->first)
                    <x-icon name="chevron-end" class="size-3 text-ink-300" />
                @endunless
                @if ($url)
                    <a href="{{ $url }}" class="hover:text-ink-900 hover:underline">{{ $label }}</a>
                @else
                    <span aria-current="page" class="text-ink-700">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

@if (count($items) > 1)
    <x-store.json-ld :data="\App\Support\StructuredData::breadcrumbs($items)" />
@endif
