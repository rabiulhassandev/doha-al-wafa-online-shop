<?php

namespace App\Http\Requests\Admin;

use App\Models\Color;
use App\Support\UniqueSlug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ColorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tidy the URL name and hex code before validating.
     */
    protected function prepareForValidation(): void
    {
        $slug = Str::slug((string) $this->input('slug'));

        if ($slug === '' && filled($this->input('name_en'))) {
            $slug = UniqueSlug::generate(Color::class, (string) $this->input('name_en'), $this->color()?->id);
        }

        $this->merge([
            'slug' => $slug,
            'hex' => Str::lower(trim((string) $this->input('hex'))),
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
            'name_en' => ['required', 'string', 'max:60'],
            'name_ar' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'string', 'max:80', Rule::unique(Color::class)->ignore($this->color())],
            'hex' => ['required', 'regex:/^#[0-9a-f]{6}$/'],
            'swatch' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_swatch' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
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
            'hex.regex' => 'Enter the color as a 6-digit hex code, for example #1f2a44.',
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
            'hex' => 'color code',
            'sort_order' => 'display order',
        ];
    }

    /**
     * Get the color attributes to save.
     *
     * @return array<string, mixed>
     */
    public function colorAttributes(): array
    {
        return [
            ...$this->safe()->except(['swatch', 'remove_swatch']),
            'sort_order' => $this->integer('sort_order'),
        ];
    }

    /**
     * Get the color being edited, if any.
     */
    private function color(): ?Color
    {
        return $this->route('color');
    }
}
