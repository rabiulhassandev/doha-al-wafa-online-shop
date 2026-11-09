<?php

namespace App\Http\Requests\Admin;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemsRequest extends FormRequest
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
            'quantities' => ['array'],
            'quantities.*' => ['required', 'integer', 'min:0', 'max:999'],
            'additions' => ['array', 'max:50'],
            'additions.*.variant_id' => ['required', 'integer'],
            'additions.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'delivery_fee' => ['required', 'decimal:0,2', 'min:0', 'max:10000'],
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
            'quantities.*' => 'quantity',
            'additions.*.quantity' => 'quantity',
            'delivery_fee' => 'delivery fee',
        ];
    }

    /**
     * Get the new quantity of each line, keyed by order item ID.
     *
     * @return array<int, int>
     */
    public function quantities(): array
    {
        return collect($this->validated('quantities', []))
            ->mapWithKeys(fn (mixed $quantity, int|string $itemId): array => [(int) $itemId => (int) $quantity])
            ->all();
    }

    /**
     * Get the pieces to add, keyed by product variant ID.
     *
     * @return array<int, int>
     */
    public function additions(): array
    {
        $additions = [];

        foreach ($this->validated('additions', []) as $line) {
            $variantId = (int) $line['variant_id'];
            $additions[$variantId] = ($additions[$variantId] ?? 0) + (int) $line['quantity'];
        }

        return $additions;
    }

    /**
     * Get the delivery fee in dirhams.
     */
    public function deliveryFee(): int
    {
        return Money::toDirhams($this->validated('delivery_fee'));
    }
}
