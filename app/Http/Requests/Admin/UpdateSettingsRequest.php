<?php

namespace App\Http\Requests\Admin;

use App\Rules\QatarPhoneNumber;
use App\Support\Money;
use App\Support\QatarPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    private const PHONE_SETTINGS = ['contact_phone', 'whatsapp_number'];

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
            'max_quantity_per_item' => ['required', 'integer', 'min:1', 'max:50'],
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
        ];
    }

    /**
     * Get the validated settings with phone numbers stored in E.164 format.
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

        return $values;
    }
}
