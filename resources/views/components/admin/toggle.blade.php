@props(['name', 'label', 'checked' => false, 'hint' => null])

{{-- Always submits a value: "0" from the hidden field, or "1" when ticked. --}}
<div>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="flex items-center gap-3 text-sm font-medium">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="1"
            @checked(old($name, $checked))
            {{ $attributes->merge(['class' => 'size-4 rounded border-sand-300 accent-maroon-700']) }}
        >
        {{ $label }}
    </label>
    @if ($hint)
        <p class="mt-1 ms-7 text-xs text-ink-500">{{ $hint }}</p>
    @endif
</div>
