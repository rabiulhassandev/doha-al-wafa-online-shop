<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\Size;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductStockRequest extends FormRequest
{
    private const MAX_STOCK = 99999;

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
            'size_ids' => ['array'],
            'size_ids.*' => ['integer', 'distinct', Rule::exists(Size::class, 'id')],
            'cells' => ['array'],
            'cells.*.*.stock' => ['required', 'integer', 'min:0', 'max:'.self::MAX_STOCK],
            'cells.*.*.original_stock' => ['required', 'integer', 'min:0'],
            'cells.*.*.offered' => ['boolean'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $productColorIds = $this->product()->colors()->pluck('id')->all();
                $submittedColorIds = array_map('intval', array_keys($this->input('cells', [])));

                if (array_diff($submittedColorIds, $productColorIds) !== []) {
                    $validator->errors()->add('cells', 'The colors on this page changed. Reload the page and enter the stock again.');
                }
            },
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
            'cells.*.*.stock.required' => 'Enter a stock number in every box (0 if you have none).',
            'cells.*.*.stock.integer' => 'Stock must be a whole number.',
            'cells.*.*.stock.min' => 'Stock cannot be negative.',
            'cells.*.*.stock.max' => 'Stock can be at most 99,999.',
        ];
    }

    /**
     * Get the IDs of the sizes the product comes in.
     *
     * @return list<int>
     */
    public function sizeIds(): array
    {
        return array_map('intval', $this->validated('size_ids', []));
    }

    /**
     * Get the grid cells with numbers and flags cast.
     *
     * @return array<int, array<int, array{stock: int, original_stock: int, offered: bool}>>
     */
    public function cells(): array
    {
        $cells = [];

        foreach ($this->validated('cells', []) as $productColorId => $sizes) {
            foreach ($sizes as $sizeId => $cell) {
                $cells[(int) $productColorId][(int) $sizeId] = [
                    'stock' => (int) $cell['stock'],
                    'original_stock' => (int) $cell['original_stock'],
                    'offered' => filter_var($cell['offered'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];
            }
        }

        return $cells;
    }

    /**
     * Get the product whose stock is being saved.
     */
    private function product(): Product
    {
        return $this->route('product');
    }
}
