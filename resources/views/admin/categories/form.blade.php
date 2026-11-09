{{-- Shared category fields. Expects $category and $parentOptions. --}}
<x-admin.card title="Name">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="name_en" label="English name" :value="$category->name_en" required maxlength="100" />
        <x-admin.input name="name_ar" label="Arabic name" :value="$category->name_ar" required maxlength="100" dir="rtl" lang="ar" />
        <div class="sm:col-span-2">
            <x-admin.input
                name="slug"
                label="URL name"
                :value="$category->slug"
                maxlength="120"
                hint="Part of the web address, such as /en/category/women. Leave empty to create it from the English name."
            />
        </div>
    </div>
</x-admin.card>

<x-admin.card title="Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-admin.select
                name="parent_id"
                label="Parent category"
                :options="$parentOptions"
                :value="$category->parent_id"
                hint="Choose a parent to make this a sub-category, for example Women › Kaftans."
            />
        </div>
        <x-admin.textarea name="description_en" label="English description" :value="$category->description_en" hint="Optional. Shown at the top of the category page." />
        <x-admin.textarea name="description_ar" label="Arabic description" :value="$category->description_ar" dir="rtl" lang="ar" />
        <div class="sm:col-span-2">
            <x-admin.image-input
                name="image"
                label="Image"
                :current-url="$category->imageUrl('image_path')"
                hint="Shown in the menu and category list. JPG, PNG or WebP up to 5 MB."
            />
        </div>
    </div>
</x-admin.card>

<x-admin.card title="Display">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="sort_order" type="number" label="Display order" :value="$category->sort_order" min="0" max="9999" hint="Lower numbers appear first." />
        <div class="sm:pt-7">
            <x-admin.toggle name="is_active" label="Show in the store" :checked="$category->is_active" />
        </div>
    </div>
</x-admin.card>
