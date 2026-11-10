{{--
    A thin bar for ranked lists: its length is the value against the largest in the list.
    The number itself sits in the next table column, so the bar adds no text.
--}}
@props(['value', 'max'])

@php
    $width = $max > 0 && $value > 0 ? max(2, $value / $max * 100) : 0;
@endphp

<span {{ $attributes->merge(['class' => 'block h-2 min-w-16']) }} aria-hidden="true">
    <span class="block h-full rounded-e bg-maroon-600" style="width: {{ $width }}%"></span>
</span>
