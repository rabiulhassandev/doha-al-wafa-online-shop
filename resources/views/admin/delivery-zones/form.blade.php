{{-- Shared delivery area fields. Expects $deliveryZone. --}}
<x-admin.card title="Area">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="name_en" label="English name" :value="$deliveryZone->name_en" required maxlength="100" placeholder="Al Wakrah" />
        <x-admin.input name="name_ar" label="Arabic name" :value="$deliveryZone->name_ar" required maxlength="100" dir="rtl" lang="ar" placeholder="الوكرة" />
    </div>
</x-admin.card>

<x-admin.card title="Fee and timing">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="fee" label="Delivery fee" :value="\App\Support\Money::toInput($deliveryZone->fee)" prefix="QAR" inputmode="decimal" required placeholder="20" />
        <x-admin.input
            name="free_over"
            label="Free delivery from (optional)"
            :value="\App\Support\Money::toInput($deliveryZone->free_over)"
            prefix="QAR"
            inputmode="decimal"
            hint="Leave empty to use the store-wide amount from Settings."
        />
        <x-admin.input name="eta_min_days" type="number" label="Earliest delivery" :value="$deliveryZone->eta_min_days" min="0" max="30" required hint="Working days after ordering. 0 means same day." />
        <x-admin.input name="eta_max_days" type="number" label="Latest delivery" :value="$deliveryZone->eta_max_days" min="0" max="30" required hint="Working days after ordering." />
    </div>
</x-admin.card>

<x-admin.card title="Display">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="sort_order" type="number" label="Display order" :value="$deliveryZone->sort_order" min="0" max="9999" hint="Lower numbers appear first in the list." />
        <div class="sm:pt-7">
            <x-admin.toggle name="is_active" label="Delivering to this area" :checked="$deliveryZone->is_active" />
        </div>
    </div>
</x-admin.card>
