<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductVariantsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Write every SKU in capitals before validating.
     */
    protected function prepareForValidation(): void
    {
        $variants = collect($this->input('variants', []))
            ->map(fn (mixed $variant): mixed => is_array($variant)
                ? [...$variant, 'sku' => Str::upper(trim((string) ($variant['sku'] ?? '')))]
                : $variant)
            ->all();

        $this->merge(['variants' => $variants]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'variants' => ['required', 'array'],
            'variants.*.sku' => ['required', 'string', 'max:60', 'regex:/^[A-Z0-9][A-Z0-9-]*$/', 'distinct'],
            'variants.*.price' => ['nullable', 'decimal:0,2', 'min:0', 'max:100000'],
        ];

        foreach (array_keys($this->input('variants', [])) as $variantId) {
            $rules["variants.{$variantId}.sku"][] = Rule::unique(ProductVariant::class, 'sku')->ignore((int) $variantId);
        }

        return $rules;
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
                $submittedIds = array_map('intval', array_keys($this->input('variants', [])));
                $productVariantIds = $this->product()->variants()->pluck('id')->all();

                if (array_diff($submittedIds, $productVariantIds) !== []) {
                    $validator->errors()->add('variants', 'The sizes on this page changed. Reload the page and try again.');
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
            'variants.*.sku.required' => 'Every size needs a SKU.',
            'variants.*.sku.regex' => 'SKUs may only contain letters, numbers and dashes.',
            'variants.*.sku.distinct' => 'Each SKU can only be used once.',
            'variants.*.sku.unique' => 'The SKU :input is already used by another product.',
            'variants.*.price.decimal' => 'Prices must be amounts in QAR, such as 185 or 185.50.',
        ];
    }

    /**
     * Get the SKU and price, in dirhams, to save for each variant ID.
     *
     * @return array<int, array{sku: string, price: int|null}>
     */
    public function variantAttributes(): array
    {
        $attributes = [];

        foreach ($this->validated('variants') as $variantId => $variant) {
            $attributes[(int) $variantId] = [
                'sku' => $variant['sku'],
                'price' => $variant['price'] === null ? null : Money::toDirhams($variant['price']),
            ];
        }

        return $attributes;
    }

    /**
     * Get the product whose variants are being saved.
     */
    private function product(): Product
    {
        return $this->route('product');
    }
}
