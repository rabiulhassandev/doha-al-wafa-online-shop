{{-- Shared size fields. Expects $size. --}}
<x-admin.card>
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="name_en" label="Size" :value="$size->name_en" required maxlength="30" placeholder="56" hint="As shoppers see it, such as M or 56." />
        <x-admin.input
            name="name_ar"
            label="Arabic name (optional)"
            :value="$size->name_ar"
            maxlength="30"
            dir="rtl"
            lang="ar"
            hint="Only needed for words, such as Free Size → مقاس واحد."
        />
        <x-admin.select
            name="group"
            label="Type"
            :options="\App\Enums\SizeGroup::options()"
            :value="$size->group?->value"
        />
        <x-admin.input name="sort_order" type="number" label="Display order" :value="$size->sort_order" min="0" max="9999" hint="Lower numbers appear first, so number sizes from smallest to largest." />
        <div class="sm:col-span-2">
            <x-admin.input
                name="slug"
                label="URL name"
                :value="$size->slug"
                maxlength="40"
                hint="Used in links that open a product in this size. Leave empty to create it from the size."
            />
        </div>
    </div>
</x-admin.card>
