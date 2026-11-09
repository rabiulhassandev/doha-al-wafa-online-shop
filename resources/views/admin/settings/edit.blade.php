<x-admin.layout title="Settings">
    <x-admin.page-header title="Settings" description="Store details shown to customers across the website." />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.card title="Store">
            <x-admin.input name="store_name" label="Store name" :value="$settings['store_name']" required maxlength="100" />
        </x-admin.card>

        <x-admin.card title="Contact" description="Leave a field empty to hide it from the store.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input
                    name="whatsapp_number"
                    type="tel"
                    label="WhatsApp number"
                    :value="$whatsAppNumber"
                    hint="A Qatar mobile number, for example 5555 1234."
                    inputmode="tel"
                />
                <x-admin.input
                    name="contact_phone"
                    type="tel"
                    label="Contact phone"
                    :value="$contactPhone"
                    hint="Mobile or landline."
                    inputmode="tel"
                />
                <div class="sm:col-span-2">
                    <x-admin.input name="contact_email" type="email" label="Contact email" :value="$settings['contact_email']" />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Social media" description="Full links starting with https://">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="instagram_url" type="url" label="Instagram" :value="$settings['instagram_url']" placeholder="https://www.instagram.com/…" />
                <x-admin.input name="tiktok_url" type="url" label="TikTok" :value="$settings['tiktok_url']" placeholder="https://www.tiktok.com/@…" />
                <x-admin.input name="facebook_url" type="url" label="Facebook" :value="$settings['facebook_url']" placeholder="https://www.facebook.com/…" />
                <x-admin.input name="snapchat_url" type="url" label="Snapchat" :value="$settings['snapchat_url']" placeholder="https://www.snapchat.com/add/…" />
            </div>
        </x-admin.card>

        <x-admin.card title="Announcement bar" description="A short message at the top of every store page, such as a delivery offer. Leave empty to hide it.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="announcement_en" label="English" :value="$settings['announcement_en']" maxlength="160" />
                <x-admin.input name="announcement_ar" label="Arabic" :value="$settings['announcement_ar']" maxlength="160" dir="rtl" lang="ar" />
            </div>
        </x-admin.card>

        <x-admin.card id="delivery" title="Delivery" description="Fees and delivery times for each area are set under Delivery areas.">
            @php
                $daysOff = array_map('intval', old('delivery_days_off', $settings['delivery_days_off'] ?? []));
                $weekdays = [6 => 'Saturday', 7 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];
            @endphp
            <div class="grid gap-6 sm:grid-cols-2">
                <x-admin.input
                    name="delivery_free_over"
                    label="Free delivery from (optional)"
                    :value="$deliveryFreeOver"
                    prefix="QAR"
                    inputmode="decimal"
                    hint="Orders of at least this amount get free delivery. Leave empty for no free delivery."
                />
                <x-admin.input
                    name="max_quantity_per_item"
                    type="number"
                    label="Most of one size per order"
                    :value="$settings['max_quantity_per_item']"
                    min="1"
                    max="50"
                    required
                    hint="Limits bulk orders of a single color and size."
                />
                <fieldset class="sm:col-span-2">
                    <legend class="text-sm font-medium">Days without delivery</legend>
                    <p class="mt-1 text-xs text-ink-500">Skipped when shoppers see their delivery dates.</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($weekdays as $isoDay => $dayName)
                            <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-lg border border-sand-300 bg-white px-3 text-sm has-checked:border-maroon-700 has-checked:bg-maroon-50">
                                <input type="checkbox" name="delivery_days_off[]" value="{{ $isoDay }}" @checked(in_array($isoDay, $daysOff, true)) class="size-4 accent-maroon-700">
                                {{ $dayName }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
        </x-admin.card>

        <x-admin.card title="Inventory">
            <div class="max-w-xs">
                <x-admin.input
                    name="low_stock_threshold"
                    type="number"
                    label="Low stock level"
                    :value="$settings['low_stock_threshold']"
                    min="0"
                    max="100"
                    required
                    hint="Sizes with this many or fewer left are flagged as low stock."
                />
            </div>
        </x-admin.card>

        <div class="sticky bottom-0 -mx-4 flex justify-end border-t border-sand-200 bg-sand-100/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0">
            <x-admin.button type="submit">Save settings</x-admin.button>
        </div>
    </form>
</x-admin.layout>
