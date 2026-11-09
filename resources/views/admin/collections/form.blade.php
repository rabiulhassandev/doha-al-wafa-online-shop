{{-- Shared collection fields. Expects $collection. --}}
<x-admin.card title="Name">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="name_en" label="English name" :value="$collection->name_en" required maxlength="100" />
        <x-admin.input name="name_ar" label="Arabic name" :value="$collection->name_ar" required maxlength="100" dir="rtl" lang="ar" />
        <div class="sm:col-span-2">
            <x-admin.input
                name="slug"
                label="URL name"
                :value="$collection->slug"
                maxlength="120"
                hint="Part of the web address, such as /en/collection/eid. Leave empty to create it from the English name."
            />
        </div>
    </div>
</x-admin.card>

<x-admin.card title="Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.textarea name="description_en" label="English description" :value="$collection->description_en" hint="Optional. Shown under the banner." />
        <x-admin.textarea name="description_ar" label="Arabic description" :value="$collection->description_ar" dir="rtl" lang="ar" />
        <div class="sm:col-span-2">
            <x-admin.image-input
                name="banner"
                label="Banner"
                :current-url="$collection->imageUrl('banner_path')"
                hint="A wide image for the top of the collection page, at least 1600 pixels wide. JPG, PNG or WebP up to 8 MB."
            />
        </div>
    </div>
</x-admin.card>

<x-admin.card title="Schedule and display" description="Leave the dates empty to show the collection all the time. Times are Qatar time.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="starts_at" type="datetime-local" label="Starts" :value="$collection->starts_at?->format('Y-m-d\TH:i')" />
        <x-admin.input name="ends_at" type="datetime-local" label="Ends" :value="$collection->ends_at?->format('Y-m-d\TH:i')" />
        <x-admin.input name="sort_order" type="number" label="Display order" :value="$collection->sort_order" min="0" max="9999" hint="Lower numbers appear first." />
        <div class="sm:pt-7">
            <x-admin.toggle name="is_active" label="Show in the store" :checked="$collection->is_active" />
        </div>
    </div>
</x-admin.card>
