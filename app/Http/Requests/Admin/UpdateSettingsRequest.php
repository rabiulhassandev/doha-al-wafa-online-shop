<?php

namespace App\Http\Requests\Admin;

use App\Rules\QatarPhoneNumber;
use App\Services\Settings;
use App\Support\Money;
use App\Support\QatarPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    private const PHONE_SETTINGS = ['contact_phone', 'whatsapp_number'];

    /**
     * Access tokens, saved encrypted. Left empty, the saved token stays; "…_clear" removes it.
     */
    private const SECRET_SETTINGS = ['meta_capi_token', 'tiktok_events_token'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', new QatarPhoneNumber],
            'whatsapp_number' => ['nullable', 'string', QatarPhoneNumber::mobile()],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'instagram_url' => ['nullable', 'url:https', 'max:255'],
            'tiktok_url' => ['nullable', 'url:https', 'max:255'],
            'facebook_url' => ['nullable', 'url:https', 'max:255'],
            'snapchat_url' => ['nullable', 'url:https', 'max:255'],
            'announcement_en' => ['nullable', 'string', 'max:160'],
            'announcement_ar' => ['nullable', 'string', 'max:160'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100'],
            'delivery_free_over' => ['nullable', 'decimal:0,2', 'min:0', 'max:100000'],
            'delivery_days_off' => ['array'],
            'delivery_days_off.*' => ['integer', 'between:1,7', 'distinct'],
            'preferred_date_enabled' => ['required', 'boolean'],
            'max_quantity_per_item' => ['required', 'integer', 'min:1', 'max:50'],
            'order_alert_email' => ['nullable', 'string', 'email', 'max:255'],
            'exchange_promise_en' => ['nullable', 'string', 'max:80'],
            'exchange_promise_ar' => ['nullable', 'string', 'max:80'],
            'consent_bar_enabled' => ['required', 'boolean'],
            'whatsapp_button_enabled' => ['required', 'boolean'],
            'meta_pixel_id' => ['nullable', 'string', 'regex:/^\d{10,20}$/'],
            'meta_capi_token' => ['nullable', 'string', 'max:1000'],
            'meta_capi_token_clear' => ['nullable', 'boolean'],
            'meta_test_event_code' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{3,20}$/'],
            'tiktok_pixel_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{10,40}$/'],
            'tiktok_events_token' => ['nullable', 'string', 'max:1000'],
            'tiktok_events_token_clear' => ['nullable', 'boolean'],
            'tiktok_test_event_code' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{3,20}$/'],
            'snap_pixel_id' => ['nullable', 'string', 'uuid'],
            'ga4_measurement_id' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{4,20}$/'],
            'feed_token' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
            'whatsapp_message_confirm_en' => ['nullable', 'string', 'max:500'],
            'whatsapp_message_confirm_ar' => ['nullable', 'string', 'max:500'],
            'whatsapp_message_on_the_way_en' => ['nullable', 'string', 'max:500'],
            'whatsapp_message_on_the_way_ar' => ['nullable', 'string', 'max:500'],
            'whatsapp_message_missed_delivery_en' => ['nullable', 'string', 'max:500'],
            'whatsapp_message_missed_delivery_ar' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'meta_pixel_id.regex' => 'The Meta pixel ID is a number of 10 to 20 digits, from Events Manager.',
            'tiktok_pixel_id.regex' => 'The TikTok pixel ID is a code of letters and digits, from Events Manager.',
            'snap_pixel_id.uuid' => 'The Snap pixel ID looks like 1a2b3c4d-1a2b-1a2b-1a2b-1a2b3c4d5e6f.',
            'ga4_measurement_id.regex' => 'The measurement ID starts with G-, such as G-AB12CD34EF.',
            'meta_test_event_code.regex' => 'Copy the test code from Events Manager, such as TEST12345.',
            'tiktok_test_event_code.regex' => 'Copy the test code from Events Manager, such as TEST12345.',
            'feed_token.regex' => 'Use 8 to 64 letters, digits, dashes or underscores.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_phone' => 'contact phone',
            'whatsapp_number' => 'WhatsApp number',
            'contact_email' => 'contact email',
            'instagram_url' => 'Instagram link',
            'tiktok_url' => 'TikTok link',
            'facebook_url' => 'Facebook link',
            'snapchat_url' => 'Snapchat link',
            'announcement_en' => 'English announcement',
            'announcement_ar' => 'Arabic announcement',
            'low_stock_threshold' => 'low stock level',
            'delivery_free_over' => 'free delivery amount',
            'delivery_days_off.*' => 'day without delivery',
            'max_quantity_per_item' => 'most of one size per order',
            'order_alert_email' => 'store alerts email',
            'exchange_promise_en' => 'English exchange promise',
            'exchange_promise_ar' => 'Arabic exchange promise',
            'whatsapp_message_confirm_en' => 'English confirmation message',
            'whatsapp_message_confirm_ar' => 'Arabic confirmation message',
            'whatsapp_message_on_the_way_en' => 'English on-the-way message',
            'whatsapp_message_on_the_way_ar' => 'Arabic on-the-way message',
            'whatsapp_message_missed_delivery_en' => 'English missed delivery message',
            'whatsapp_message_missed_delivery_ar' => 'Arabic missed delivery message',
            'meta_capi_token' => 'Conversions API token',
            'tiktok_events_token' => 'Events API token',
            'feed_token' => 'feed password',
        ];
    }

    /**
     * Get the validated settings with phone numbers stored in E.164 format and tokens encrypted.
     *
     * @return array<string, mixed>
     */
    public function settingValues(): array
    {
        $values = $this->validated();

        foreach (self::PHONE_SETTINGS as $key) {
            $values[$key] = QatarPhone::tryParse($values[$key])?->e164();
        }

        $values['low_stock_threshold'] = (int) $values['low_stock_threshold'];
        $values['max_quantity_per_item'] = (int) $values['max_quantity_per_item'];
        $freeOver = $values['delivery_free_over'] ?? null;
        $values['delivery_free_over'] = $freeOver === null ? null : Money::toDirhams($freeOver);
        $values['delivery_days_off'] = array_map('intval', $this->validated('delivery_days_off', []));
        $values['preferred_date_enabled'] = $this->boolean('preferred_date_enabled');
        $values['consent_bar_enabled'] = $this->boolean('consent_bar_enabled');
        $values['whatsapp_button_enabled'] = $this->boolean('whatsapp_button_enabled');
        if (filled($values['feed_token'] ?? null)) {
            $values['feed_token'] = Settings::encryptSecret($values['feed_token']);
        }

        foreach (self::SECRET_SETTINGS as $key) {
            unset($values[$key], $values["{$key}_clear"]);

            if ($this->boolean("{$key}_clear")) {
                $values[$key] = null;
            } elseif (filled($this->validated($key))) {
                $values[$key] = Settings::encryptSecret(trim($this->validated($key)));
            }
        }

        return $values;
    }
}
