{{-- Shared banner fields. Expects $banner. --}}
<x-admin.card title="Images">
    <div class="grid gap-6 sm:grid-cols-2">
        <x-admin.image-input
            name="image"
            label="Desktop image"
            :current-url="$banner->imageUrl('image_path')"
            hint="Wide, about 1920 × 840 pixels. Keep important details in the center."
        />
        <x-admin.image-input
            name="mobile_image"
            label="Phone image (recommended)"
            :current-url="$banner->imageUrl('mobile_image_path')"
            hint="Portrait, about 1080 × 1350 pixels. Most shoppers see this one."
        />
    </div>
</x-admin.card>

<x-admin.card title="Text" description="Optional. Shown over the image.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="title_en" label="English title" :value="$banner->title_en" maxlength="100" />
        <x-admin.input name="title_ar" label="Arabic title" :value="$banner->title_ar" maxlength="100" dir="rtl" lang="ar" />
        <x-admin.input name="subtitle_en" label="English subtitle" :value="$banner->subtitle_en" maxlength="200" />
        <x-admin.input name="subtitle_ar" label="Arabic subtitle" :value="$banner->subtitle_ar" maxlength="200" dir="rtl" lang="ar" />
        <x-admin.input name="button_label_en" label="English button" :value="$banner->button_label_en" maxlength="40" placeholder="Shop now" />
        <x-admin.input name="button_label_ar" label="Arabic button" :value="$banner->button_label_ar" maxlength="40" dir="rtl" lang="ar" placeholder="تسوقي الآن" />
        <div class="sm:col-span-2">
            <x-admin.input
                name="link"
                label="Link"
                :value="$banner->link"
                maxlength="255"
                placeholder="collection/eid"
                hint="A store page such as collection/eid, category/women or shop?color=navy (opened in the shopper’s language), or a full link starting with https://."
            />
        </div>
    </div>
</x-admin.card>

<x-admin.card title="Schedule and display" description="Leave the dates empty to show the banner all the time. Times are Qatar time.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="starts_at" type="datetime-local" label="Starts" :value="$banner->starts_at?->format('Y-m-d\TH:i')" />
        <x-admin.input name="ends_at" type="datetime-local" label="Ends" :value="$banner->ends_at?->format('Y-m-d\TH:i')" />
        <x-admin.input name="sort_order" type="number" label="Display order" :value="$banner->sort_order" min="0" max="9999" hint="Lower numbers appear first." />
        <div class="sm:pt-7">
            <x-admin.toggle name="is_active" label="Show on the home page" :checked="$banner->is_active" />
        </div>
    </div>
</x-admin.card>
