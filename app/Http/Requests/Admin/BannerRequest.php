<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
{
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
            'title_en' => ['nullable', 'string', 'max:100'],
            'title_ar' => ['nullable', 'string', 'max:100'],
            'subtitle_en' => ['nullable', 'string', 'max:200'],
            'subtitle_ar' => ['nullable', 'string', 'max:200'],
            'button_label_en' => ['nullable', 'string', 'max:40'],
            'button_label_ar' => ['nullable', 'string', 'max:40'],
            // A store path such as "collection/eid" or "shop?color=navy", or a full https:// link.
            'link' => ['nullable', 'string', 'max:255', 'regex:#^(https://\S+|/?[A-Za-z0-9][A-Za-z0-9/_.,=&?%-]*)$#'],
            'image' => [Rule::requiredIf($this->banner() === null), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=4096,max_height=4096'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=4096,max_height=4096'],
            'remove_mobile_image' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($this->filled('starts_at'), 'after:starts_at')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
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
            'link.regex' => 'Use a store path such as collection/eid, or a full link starting with https://.',
            'image.required' => 'Upload a desktop image.',
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
            'image' => 'desktop image',
            'mobile_image' => 'mobile image',
            'starts_at' => 'start date',
            'ends_at' => 'end date',
            'sort_order' => 'display order',
        ];
    }

    /**
     * Get the banner attributes to save.
     *
     * @return array<string, mixed>
     */
    public function bannerAttributes(): array
    {
        return [
            ...$this->safe()->except(['image', 'mobile_image', 'remove_mobile_image']),
            'sort_order' => $this->integer('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * Get the banner being edited, if any.
     */
    private function banner(): ?Banner
    {
        return $this->route('banner');
    }
}
