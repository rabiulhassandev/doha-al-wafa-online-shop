{{-- Product detail fields. Expects $product, $categoryOptions, $collections, $sizeGuideOptions and $statusOptions. --}}
@php
    $selectedCollectionIds = array_map('intval', old('collection_ids', $product->exists ? $product->collections->modelKeys() : []));
@endphp

<x-admin.card title="Name">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="name_en" label="English name" :value="$product->name_en" required maxlength="150" placeholder="Classic crepe jelabiya" />
        <x-admin.input name="name_ar" label="Arabic name" :value="$product->name_ar" required maxlength="150" dir="rtl" lang="ar" placeholder="جلابية كريب كلاسيكية" />
        <x-admin.input
            name="slug"
            label="URL name"
            :value="$product->slug"
            maxlength="160"
            hint="Part of the product link, such as /en/products/classic-crepe-jelabiya. Leave empty to create it from the English name."
        />
        <x-admin.input
            name="code"
            label="Product code"
            :value="$product->code"
            maxlength="12"
            hint="Starts every SKU, such as DW0012-NAVY-56. Leave empty to number it automatically."
        />
    </div>
</x-admin.card>

<x-admin.card title="Price" description="In Qatari riyals. Colors and sizes can have their own price on the Sizes & stock tab.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="price" label="Selling price" :value="\App\Support\Money::toInput($product->price)" prefix="QAR" inputmode="decimal" required placeholder="185" />
        <x-admin.input
            name="compare_at_price"
            label="Original price (optional)"
            :value="\App\Support\Money::toInput($product->compare_at_price)"
            prefix="QAR"
            inputmode="decimal"
            placeholder="220"
            hint="Shown crossed out next to the selling price during a sale."
        />
    </div>
</x-admin.card>

<x-admin.card title="Organize">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.select name="category_id" label="Category" :options="['' => 'Choose a category'] + $categoryOptions" :value="$product->category_id" required />
        <x-admin.select name="size_guide_id" label="Size guide" :options="$sizeGuideOptions" :value="$product->size_guide_id" />

        <fieldset class="sm:col-span-2">
            <legend class="text-sm font-medium text-ink-900">Collections</legend>
            @if ($collections->isEmpty())
                <p class="mt-1.5 text-sm text-ink-500">No collections yet.</p>
            @else
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($collections as $collection)
                        <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-lg border border-sand-300 bg-white px-3 text-sm has-checked:border-maroon-700 has-checked:bg-maroon-50">
                            <input type="checkbox" name="collection_ids[]" value="{{ $collection->id }}" @checked(in_array($collection->id, $selectedCollectionIds, true)) class="size-4 accent-maroon-700">
                            {{ $collection->name_en }}
                        </label>
                    @endforeach
                </div>
            @endif
            @error('collection_ids.*')
                <p class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
            @enderror
        </fieldset>
    </div>
</x-admin.card>

<x-admin.card title="Description" description="Plain text. Line breaks are kept.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.textarea name="description_en" label="English description" :value="$product->description_en" rows="5" />
        <x-admin.textarea name="description_ar" label="Arabic description" :value="$product->description_ar" rows="5" dir="rtl" lang="ar" />
        <x-admin.textarea name="details_en" label="English fabric and care" :value="$product->details_en" placeholder="100% crepe. Hand wash cold." />
        <x-admin.textarea name="details_ar" label="Arabic fabric and care" :value="$product->details_ar" dir="rtl" lang="ar" placeholder="١٠٠٪ كريب. غسيل يدوي بماء بارد." />
    </div>
</x-admin.card>

<x-admin.card title="Search engines and sharing" description="Optional. Leave empty to use the product name and description.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="meta_title_en" label="English title" :value="$product->meta_title_en" maxlength="70" />
        <x-admin.input name="meta_title_ar" label="Arabic title" :value="$product->meta_title_ar" maxlength="70" dir="rtl" lang="ar" />
        <x-admin.textarea name="meta_description_en" label="English description" :value="$product->meta_description_en" maxlength="160" rows="2" />
        <x-admin.textarea name="meta_description_ar" label="Arabic description" :value="$product->meta_description_ar" maxlength="160" rows="2" dir="rtl" lang="ar" />
    </div>
</x-admin.card>

<x-admin.card title="Visibility">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.select
            name="status"
            label="Status"
            :options="$statusOptions"
            :value="$product->status?->value"
            hint="Draft and Archived products are hidden from shoppers."
        />
        <div class="sm:pt-7">
            <x-admin.toggle name="is_featured" label="Feature on the home page" :checked="$product->is_featured" />
        </div>
    </div>
</x-admin.card>
