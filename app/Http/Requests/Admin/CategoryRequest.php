<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Support\UniqueSlug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tidy the URL name, or build one from the English name when it is left empty.
     */
    protected function prepareForValidation(): void
    {
        $slug = Str::slug((string) $this->input('slug'));

        if ($slug === '' && filled($this->input('name_en'))) {
            $slug = UniqueSlug::generate(Category::class, (string) $this->input('name_en'), $this->category()?->id);
        }

        $this->merge(['slug' => $slug]);
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
            'slug' => ['required', 'string', 'max:120', Rule::unique(Category::class)->ignore($this->category())],
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')->whereNull('parent_id')],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
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
                $category = $this->category();

                if ($category === null || blank($this->input('parent_id')) || $validator->errors()->has('parent_id')) {
                    return;
                }

                if ($this->integer('parent_id') === $category->id) {
                    $validator->errors()->add('parent_id', "A category can't be its own parent.");
                } elseif ($category->children()->exists()) {
                    $validator->errors()->add('parent_id', 'This category has sub-categories, so it must stay at the top level.');
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
            'parent_id.exists' => 'Choose a top-level category as the parent.',
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
            'parent_id' => 'parent category',
            'description_en' => 'English description',
            'description_ar' => 'Arabic description',
            'sort_order' => 'display order',
        ];
    }

    /**
     * Get the category attributes to save.
     *
     * @return array<string, mixed>
     */
    public function categoryAttributes(): array
    {
        return [
            ...$this->safe()->except(['image', 'remove_image']),
            'sort_order' => $this->integer('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * Get the category being edited, if any.
     */
    private function category(): ?Category
    {
        return $this->route('category');
    }
}
