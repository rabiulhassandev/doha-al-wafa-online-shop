{{-- A round color preview. Expects $color and optional $sizeClass. --}}
@if ($color->swatch_path)
    <img src="{{ $color->imageUrl('swatch_path') }}" alt="" class="{{ $sizeClass ?? 'size-10' }} rounded-full object-cover ring-1 ring-ink-900/15">
@else
    <span class="{{ $sizeClass ?? 'size-10' }} block rounded-full ring-1 ring-ink-900/15" style="background-color: {{ $color->hex }}"></span>
@endif
