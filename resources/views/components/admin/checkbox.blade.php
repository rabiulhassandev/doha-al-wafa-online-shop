@props(['name', 'label', 'checked' => false])

<label class="flex items-center gap-3 text-sm">
    <input
        type="checkbox"
        name="{{ $name }}"
        value="1"
        @checked(old($name, $checked))
        {{ $attributes->merge(['class' => 'size-4 rounded border-sand-300 accent-maroon-700']) }}
    >
    {{ $label }}
</label>
