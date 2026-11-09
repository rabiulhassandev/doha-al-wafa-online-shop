<?php

namespace App\Http\Requests\Admin;

use App\Enums\SizeGroup;
use App\Models\Size;
use App\Support\UniqueSlug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SizeRequest extends FormRequest
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
            $slug = UniqueSlug::generate(Size::class, (string) $this->input('name_en'), $this->size()?->id);
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
            'name_en' => ['required', 'string', 'max:30'],
            'name_ar' => ['nullable', 'string', 'max:30'],
            'slug' => ['required', 'string', 'max:40', Rule::unique(Size::class)->ignore($this->size())],
            'group' => ['required', Rule::enum(SizeGroup::class)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
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
            'name_en' => 'size name',
            'name_ar' => 'Arabic name',
            'slug' => 'URL name',
            'group' => 'size type',
            'sort_order' => 'display order',
        ];
    }

    /**
     * Get the size attributes to save.
     *
     * @return array<string, mixed>
     */
    public function sizeAttributes(): array
    {
        return [
            ...$this->validated(),
            'sort_order' => $this->integer('sort_order'),
        ];
    }

    /**
     * Get the size being edited, if any.
     */
    private function size(): ?Size
    {
        return $this->route('size');
    }
}
