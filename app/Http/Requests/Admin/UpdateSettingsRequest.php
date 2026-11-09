<?php

namespace App\Http\Requests\Admin;

use App\Rules\QatarPhoneNumber;
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

        return $values;
    }
}
