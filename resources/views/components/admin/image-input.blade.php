@props(['name', 'label', 'currentUrl' => null, 'hint' => null])

<div x-data="{ preview: @js($currentUrl), removed: false }">
    <span class="block text-sm font-medium text-ink-900">{{ $label }}</span>

    <div class="mt-1.5 flex items-start gap-4">
        <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-sand-200 bg-sand-50">
            <template x-if="preview && ! removed">
                <img x-bind:src="preview" alt="" class="size-full object-cover">
            </template>
            <template x-if="! preview || removed">
                <x-icon name="image" class="size-7 text-ink-300" />
            </template>
        </div>

        <div class="min-w-0 space-y-2">
            <input
                type="file"
                id="{{ $name }}"
                name="{{ $name }}"
                accept="image/jpeg,image/png,image/webp"
                x-on:change="preview = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : @js($currentUrl); removed = false"
                class="block w-full text-sm text-ink-700 file:me-3 file:h-9 file:rounded-lg file:border-0 file:bg-sand-100 file:px-3 file:text-sm file:font-medium hover:file:bg-sand-200"
            >

            @if ($currentUrl)
                <label class="flex items-center gap-2 text-sm text-ink-700">
                    <input type="hidden" name="remove_{{ $name }}" value="0">
                    <input type="checkbox" name="remove_{{ $name }}" value="1" x-model="removed" class="size-4 rounded border-sand-300 accent-maroon-700">
                    Remove current image
                </label>
            @endif

            @if ($hint)
                <p class="text-xs text-ink-500">{{ $hint }}</p>
            @endif

            @error($name)
                <p class="text-xs font-medium text-danger-700">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
