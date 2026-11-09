{{-- Shared color fields. Expects $color. --}}
<x-admin.card title="Name">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="name_en" label="English name" :value="$color->name_en" required maxlength="60" placeholder="Navy" />
        <x-admin.input name="name_ar" label="Arabic name" :value="$color->name_ar" required maxlength="60" dir="rtl" lang="ar" placeholder="كحلي" />
        <div class="sm:col-span-2">
            <x-admin.input
                name="slug"
                label="URL name"
                :value="$color->slug"
                maxlength="80"
                hint="Used in links that open a product in this color, such as ?color=navy. Leave empty to create it from the English name."
            />
        </div>
    </div>
</x-admin.card>

<x-admin.card title="Appearance">
    <div class="grid gap-6 sm:grid-cols-2">
        <div x-data="{ hex: @js(old('hex', $color->hex)) }">
            <label for="hex" class="block text-sm font-medium text-ink-900">Color</label>
            <div class="mt-1.5 flex items-center gap-3">
                <input
                    type="color"
                    x-model="hex"
                    class="h-11 w-14 cursor-pointer rounded-lg border border-sand-300 bg-white p-1"
                    aria-label="Pick a color"
                >
                <input
                    type="text"
                    id="hex"
                    name="hex"
                    x-model="hex"
                    maxlength="7"
                    required
                    @class([
                        'h-11 w-32 rounded-lg border bg-white px-3 font-mono text-sm focus:ring-2 focus:ring-maroon-100 focus:outline-none',
                        'border-danger-700' => $errors->has('hex'),
                        'border-sand-300 focus:border-maroon-700' => ! $errors->has('hex'),
                    ])
                >
            </div>
            <p class="mt-1.5 text-xs text-ink-500">Shown as the swatch when there is no fabric photo.</p>
            @error('hex')
                <p class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
            @enderror
        </div>

        <x-admin.image-input
            name="swatch"
            label="Fabric photo (optional)"
            :current-url="$color->imageUrl('swatch_path')"
            hint="For prints and patterns. A close-up of the fabric, cropped to a square."
        />

        <x-admin.input name="sort_order" type="number" label="Display order" :value="$color->sort_order" min="0" max="9999" hint="Lower numbers appear first." />
    </div>
</x-admin.card>
