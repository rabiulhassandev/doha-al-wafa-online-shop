<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockMovementReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    /**
     * The reasons a team member can pick by hand.
     */
    public const MANUAL_REASONS = [
        StockMovementReason::Received,
        StockMovementReason::Damaged,
        StockMovementReason::Recount,
    ];

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
            'reason' => ['required', Rule::enum(StockMovementReason::class)->only(self::MANUAL_REASONS)],
            'quantity' => [
                'required',
                'integer',
                Rule::when($this->input('reason') === StockMovementReason::Recount->value, 'min:0', 'min:1'),
                'max:99999',
            ],
            'note' => ['nullable', 'string', 'max:200'],
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
            'reason.required' => 'Choose what happened to the stock.',
            'quantity.min' => 'Enter a quantity of at least :min.',
        ];
    }
}
