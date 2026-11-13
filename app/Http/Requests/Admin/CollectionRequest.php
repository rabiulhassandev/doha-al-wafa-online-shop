<?php

namespace App\Http\Requests\Admin;

use App\Models\Collection;
use App\Support\UniqueSlug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CollectionRequest extends FormRequest
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
            $slug = UniqueSlug::generate(Collection::class, (string) $this->input('name_en'), $this->collection()?->id);
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
            'slug' => ['required', 'string', 'max:120', Rule::unique(Collection::class)->ignore($this->collection())],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=4096,max_height=4096'],
            'remove_banner' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($this->filled('starts_at'), 'after:starts_at')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
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
            'description_en' => 'English description',
            'description_ar' => 'Arabic description',
            'starts_at' => 'start date',
            'ends_at' => 'end date',
            'sort_order' => 'display order',
        ];
    }

    /**
     * Get the collection attributes to save.
     *
     * @return array<string, mixed>
     */
    public function collectionAttributes(): array
    {
        return [
            ...$this->safe()->except(['banner', 'remove_banner']),
            'sort_order' => $this->integer('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * Get the collection being edited, if any.
     */
    private function collection(): ?Collection
    {
        return $this->route('collection');
    }
}
