<?php

namespace App\Http\Requests\Storefront\Account;

use App\Support\Digits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddressRequest extends FormRequest
{
    private const PLATE_NUMBERS = ['zone_number', 'street_number', 'building_number'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept numbers typed on Arabic keyboards.
     */
    protected function prepareForValidation(): void
    {
        foreach (self::PLATE_NUMBERS as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => Digits::toWestern($this->input($field))]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:40'],
            'delivery_zone_id' => ['required', 'integer', Rule::exists('delivery_zones', 'id')->where('is_active', true)],
            'zone_number' => ['required', 'regex:/^\d{1,4}$/'],
            'street_number' => ['required', 'regex:/^\d{1,4}$/'],
            'building_number' => ['required', 'regex:/^\d{1,4}$/'],
            'unit' => ['nullable', 'string', 'max:60'],
            'landmark' => ['nullable', 'string', 'max:200'],
            'is_default' => ['boolean'],
        ];
    }

    /**
     * Get the error messages shoppers see, in their language.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $tooLong = __('Please shorten this to :max characters.');

        return [
            'label.max' => $tooLong,
            'delivery_zone_id' => __('Choose your area.'),
            'zone_number' => __('Enter the zone number from the blue plate.'),
            'street_number' => __('Enter the street number from the blue plate.'),
            'building_number' => __('Enter the building number from the blue plate.'),
            'unit.max' => $tooLong,
            'landmark.max' => $tooLong,
        ];
    }

    /**
     * Get the address fields, with plate numbers written without leading zeros.
     *
     * @return array<string, mixed>
     */
    public function addressAttributes(): array
    {
        return [
            'label' => $this->validated('label'),
            'delivery_zone_id' => (int) $this->validated('delivery_zone_id'),
            'zone_number' => (string) (int) $this->validated('zone_number'),
            'street_number' => (string) (int) $this->validated('street_number'),
            'building_number' => (string) (int) $this->validated('building_number'),
            'unit' => $this->validated('unit'),
            'landmark' => $this->validated('landmark'),
        ];
    }
}
