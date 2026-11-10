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
                <div class="sm:col-span-2">
                    <x-admin.toggle
                        name="whatsapp_button_enabled"
                        label="Show a WhatsApp button on every store page"
                        :checked="(bool) $settings['whatsapp_button_enabled']"
                        hint="A round button in the corner that opens a chat with the WhatsApp number above."
                    />
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
                <div class="sm:col-span-2">
                    <x-admin.toggle
                        name="preferred_date_enabled"
                        label="Let shoppers choose a preferred delivery date"
                        :checked="(bool) $settings['preferred_date_enabled']"
                        :hint="'Checkout offers the next '.\App\Models\DeliveryZone::CHOOSABLE_DAYS.' delivery days for the shopper\'s area. Choosing one is optional.'"
                    />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Store alerts">
            <div class="max-w-md">
                <x-admin.input
                    name="order_alert_email"
                    type="email"
                    label="Email store alerts to (optional)"
                    :value="$settings['order_alert_email']"
                    hint="Each new website order, and a summary of the sizes running low every morning at 8:00. Leave empty to send none."
                />
            </div>
        </x-admin.card>

        <x-admin.card title="Trust badges" description="A short exchange promise, shown with cash on delivery and delivery across Qatar. Leave empty to leave it out.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="exchange_promise_en" label="English exchange promise" :value="$settings['exchange_promise_en']" maxlength="80" placeholder="Exchange within 7 days" />
                <x-admin.input name="exchange_promise_ar" label="Arabic exchange promise" :value="$settings['exchange_promise_ar']" maxlength="80" dir="rtl" lang="ar" placeholder="استبدال خلال 7 أيام" />
            </div>
        </x-admin.card>

        <x-admin.card title="WhatsApp messages" description="Ready-written messages on each order page, in the customer's language. Leave a box empty to use the wording shown in it. :name, :number, :total and :store are filled in for each order.">
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach (\App\Services\WhatsAppMessages::DEFAULTS as $key => $message)
                    @foreach (['en' => 'English', 'ar' => 'Arabic'] as $locale => $language)
                        <x-admin.textarea
                            :name="'whatsapp_message_'.$key.'_'.$locale"
                            :label="$message['label'].' ('.$language.')'"
                            :value="$settings['whatsapp_message_'.$key.'_'.$locale]"
                            :placeholder="\App\Services\WhatsAppMessages::defaultText($key, $locale)"
                            rows="3"
                            maxlength="500"
                            :dir="$locale === 'ar' ? 'rtl' : 'ltr'"
                            :lang="$locale"
                        />
                    @endforeach
                @endforeach
            </div>
        </x-admin.card>

        <x-admin.card title="Privacy">
            <x-admin.toggle
                name="consent_bar_enabled"
                label="Ask visitors before ad tracking"
                :checked="(bool) $settings['consent_bar_enabled']"
                hint="Shows a small bar where visitors accept or refuse ad cookies. Turn it on before adding Meta, TikTok or Snapchat tracking: those load only for visitors who accept."
            />
        </x-admin.card>

        <x-admin.card id="tracking" title="Ads and analytics" description="Paste the IDs from each platform to measure visits, adds to bag and orders. Leave a field empty to turn that platform off. With the consent bar on, they load only for visitors who accept.">
            <div class="space-y-8">
                <fieldset class="grid gap-5 sm:grid-cols-2">
                    <legend class="mb-4 text-sm font-semibold">Meta (Facebook and Instagram)</legend>
                    <x-admin.input name="meta_pixel_id" label="Pixel ID" :value="$settings['meta_pixel_id']" inputmode="numeric" placeholder="1234567890123456" hint="Events Manager → your data source → Settings." />
                    <x-admin.input name="meta_test_event_code" label="Test code (optional)" :value="$settings['meta_test_event_code']" placeholder="TEST12345" hint="Only while checking orders in Test events. Clear it afterwards." />
                    <div class="space-y-2 sm:col-span-2">
                        <x-admin.input
                            name="meta_capi_token"
                            type="password"
                            label="Conversions API token (optional)"
                            autocomplete="new-password"
                            :hint="$secretsSaved['meta_capi_token'] ? 'A token is saved. Leave this empty to keep it.' : 'Sends each order from the server too, so ad blockers and in-app browsers don’t hide sales. Settings → Conversions API → Generate access token.'"
                        />
                        @if ($secretsSaved['meta_capi_token'])
                            <x-admin.checkbox name="meta_capi_token_clear" label="Remove the saved token" />
                        @endif
                    </div>
                </fieldset>

                <fieldset class="grid gap-5 sm:grid-cols-2">
                    <legend class="mb-4 text-sm font-semibold">TikTok</legend>
                    <x-admin.input name="tiktok_pixel_id" label="Pixel ID" :value="$settings['tiktok_pixel_id']" placeholder="C1A2B3C4D5E6F7G8H9I0" hint="Ads Manager → Tools → Events → Web events." />
                    <x-admin.input name="tiktok_test_event_code" label="Test code (optional)" :value="$settings['tiktok_test_event_code']" placeholder="TEST12345" hint="Only while testing. Clear it afterwards." />
                    <div class="space-y-2 sm:col-span-2">
                        <x-admin.input
                            name="tiktok_events_token"
                            type="password"
                            label="Events API access token (optional)"
                            autocomplete="new-password"
                            :hint="$secretsSaved['tiktok_events_token'] ? 'A token is saved. Leave this empty to keep it.' : 'Sends each order from the server too. Your pixel → Settings → Events API → Generate access token.'"
                        />
                        @if ($secretsSaved['tiktok_events_token'])
                            <x-admin.checkbox name="tiktok_events_token_clear" label="Remove the saved token" />
                        @endif
                    </div>
                </fieldset>

                <fieldset class="grid gap-5 sm:grid-cols-2">
                    <legend class="mb-4 text-sm font-semibold">Snapchat and Google</legend>
                    <x-admin.input name="snap_pixel_id" label="Snap pixel ID" :value="$settings['snap_pixel_id']" placeholder="1a2b3c4d-1a2b-1a2b-1a2b-1a2b3c4d5e6f" hint="Snapchat Ads Manager → Events Manager." />
                    <x-admin.input name="ga4_measurement_id" label="Google Analytics measurement ID" :value="$settings['ga4_measurement_id']" placeholder="G-AB12CD34EF" hint="Admin → Data streams → your website." />
                </fieldset>
            </div>
        </x-admin.card>

        <x-admin.card id="feeds" title="Catalog feeds" description="Product lists for Meta Commerce Manager and TikTok Catalog, for shop tabs and catalog ads. Add a feed as a scheduled data feed with the address below; the platforms fetch it every day. Every color and size is its own item.">
            <div class="space-y-5">
                <div class="max-w-md">
                    <x-admin.input
                        name="feed_token"
                        label="Feed password (optional)"
                        :value="$settings['feed_token']"
                        maxlength="64"
                        hint="Adds ?token=… to the addresses so only the platforms can read your feeds. 8 or more letters and digits. Save, then copy the addresses again."
                    />
                </div>
                <dl class="grid gap-3 text-sm">
                    @foreach ($feedUrls as $feedLabel => $feedUrl)
                        <div class="rounded-lg bg-sand-50 px-3 py-2">
                            <dt class="text-xs text-ink-500">{{ $feedLabel }}</dt>
                            <dd class="font-mono break-all select-all">{{ $feedUrl }}</dd>
                        </div>
                    @endforeach
                </dl>
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
