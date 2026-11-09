<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\SizeGuide;
use App\Support\Money;
use App\Support\UniqueSlug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    private const MAX_PRICE = 100000;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tidy the URL name and product code before validating.
     */
    protected function prepareForValidation(): void
    {
        $slug = Str::slug((string) $this->input('slug'));

        if ($slug === '' && filled($this->input('name_en'))) {
            $slug = UniqueSlug::generate(Product::class, (string) $this->input('name_en'), $this->product()?->id);
        }

        $this->merge([
            'slug' => $slug,
            'code' => Str::upper(trim((string) $this->input('code'))) ?: null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:150'],
            'name_ar' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160', Rule::unique(Product::class)->ignore($this->product())],
            'code' => ['nullable', 'string', 'regex:/^[A-Z0-9]{2,12}$/', Rule::unique(Product::class)->ignore($this->product())],
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'collection_ids' => ['array'],
            'collection_ids.*' => ['integer', 'distinct', Rule::exists(Collection::class, 'id')],
            'size_guide_id' => ['nullable', 'integer', Rule::exists(SizeGuide::class, 'id')],
            'price' => ['required', 'decimal:0,2', 'min:0', 'max:'.self::MAX_PRICE],
            'compare_at_price' => ['nullable', 'decimal:0,2', 'gt:price', 'max:'.self::MAX_PRICE],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'is_featured' => ['boolean'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'details_en' => ['nullable', 'string', 'max:2000'],
            'details_ar' => ['nullable', 'string', 'max:2000'],
            'meta_title_en' => ['nullable', 'string', 'max:70'],
            'meta_title_ar' => ['nullable', 'string', 'max:70'],
            'meta_description_en' => ['nullable', 'string', 'max:160'],
            'meta_description_ar' => ['nullable', 'string', 'max:160'],
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
            'code.regex' => 'The product code may only contain 2 to 12 letters and numbers.',
            'compare_at_price.gt' => 'The original price must be higher than the selling price.',
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
            'slug' => 'URL name',
            'code' => 'product code',
            'category_id' => 'category',
            'collection_ids.*' => 'collection',
            'size_guide_id' => 'size guide',
            'compare_at_price' => 'original price',
            'description_en' => 'English description',
            'description_ar' => 'Arabic description',
            'details_en' => 'English fabric and care',
            'details_ar' => 'Arabic fabric and care',
            'meta_title_en' => 'English search title',
            'meta_title_ar' => 'Arabic search title',
            'meta_description_en' => 'English search description',
            'meta_description_ar' => 'Arabic search description',
        ];
    }

    /**
     * Get the product attributes to save, with prices in dirhams.
     *
     * @return array<string, mixed>
     */
    public function productAttributes(): array
    {
        $compareAtPrice = $this->validated('compare_at_price');

        return [
            ...$this->safe()->except(['collection_ids', 'price', 'compare_at_price', 'is_featured']),
            'price' => Money::toDirhams($this->validated('price')),
            'compare_at_price' => $compareAtPrice === null ? null : Money::toDirhams($compareAtPrice),
            'is_featured' => $this->boolean('is_featured'),
        ];
    }

    /**
     * Get the IDs of the collections the product should appear in.
     *
     * @return list<int>
     */
    public function collectionIds(): array
    {
        return array_map('intval', $this->validated('collection_ids', []));
    }

    /**
     * Get the product being edited, if any.
     */
    private function product(): ?Product
    {
        return $this->route('product');
    }
}
