<?php

namespace App\Http\Requests\Admin;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeliveryZoneRequest extends FormRequest
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
            'name_en' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:100'],
            'fee' => ['required', 'decimal:0,2', 'min:0', 'max:1000'],
            'free_over' => ['nullable', 'decimal:0,2', 'min:0', 'max:100000'],
            'eta_min_days' => ['required', 'integer', 'min:0', 'max:30'],
            'eta_max_days' => ['required', 'integer', 'gte:eta_min_days', 'max:30'],
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
            'eta_max_days.gte' => 'The latest delivery day cannot be before the earliest.',
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
            'name_en' => 'English name',
            'name_ar' => 'Arabic name',
            'fee' => 'delivery fee',
            'free_over' => 'free delivery amount',
            'eta_min_days' => 'earliest delivery day',
            'eta_max_days' => 'latest delivery day',
            'sort_order' => 'display order',
        ];
    }

    /**
     * Get the area attributes to save, with amounts in dirhams.
     *
     * @return array<string, mixed>
     */
    public function zoneAttributes(): array
    {
        $freeOver = $this->validated('free_over');

        return [
            ...$this->safe()->only(['name_en', 'name_ar', 'eta_min_days', 'eta_max_days']),
            'fee' => Money::toDirhams($this->validated('fee')),
            'free_over' => $freeOver === null ? null : Money::toDirhams($freeOver),
            'sort_order' => $this->integer('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
