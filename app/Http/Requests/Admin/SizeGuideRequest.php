<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SizeGuideRequest extends FormRequest
{
    private const MAX_COLUMNS = 6;

    private const MAX_ROWS = 30;

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
            'name' => ['required', 'string', 'max:100'],
            'columns' => ['required', 'array', 'min:1', 'max:'.self::MAX_COLUMNS],
            'columns.*.en' => ['required', 'string', 'max:40'],
            'columns.*.ar' => ['required', 'string', 'max:40'],
            'rows' => ['required', 'array', 'min:1', 'max:'.self::MAX_ROWS],
            'rows.*.size' => ['required', 'string', 'max:20'],
            'rows.*.values' => ['required', 'array'],
            'rows.*.values.*' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'notes_en' => ['nullable', 'string', 'max:500'],
            'notes_ar' => ['nullable', 'string', 'max:500'],
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
                if ($validator->errors()->hasAny(['columns', 'rows'])) {
                    return;
                }

                $columnCount = count($this->input('columns'));

                foreach (array_values($this->input('rows')) as $index => $row) {
                    if (count($row['values'] ?? []) !== $columnCount) {
                        $validator->errors()->add('rows', 'Row '.($index + 1).' needs a value for every measurement.');
                    }
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
            'columns.*.en.required' => 'Every measurement needs an English name.',
            'columns.*.ar.required' => 'Every measurement needs an Arabic name.',
            'rows.*.size.required' => 'Every row needs a size.',
            'rows.*.values.*.numeric' => 'Measurements must be numbers in centimeters.',
            'rows.*.values.*.max' => 'Measurements can be at most 500 cm.',
        ];
    }

    /**
     * Get the size guide attributes to save, with rows and columns re-indexed and numbers cast.
     *
     * @return array{name: string, columns: list<array{en: string, ar: string}>, rows: list<array{size: string, values: list<float|null>}>, notes_en: ?string, notes_ar: ?string}
     */
    public function sizeGuideAttributes(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'columns' => array_map(
                fn (array $column): array => ['en' => $column['en'], 'ar' => $column['ar']],
                array_values($validated['columns']),
            ),
            'rows' => array_map(fn (array $row): array => [
                'size' => $row['size'],
                'values' => array_map(
                    fn (mixed $value): ?float => $value === null ? null : round((float) $value, 1),
                    array_values($row['values']),
                ),
            ], array_values($validated['rows'])),
            'notes_en' => $validated['notes_en'] ?? null,
            'notes_ar' => $validated['notes_ar'] ?? null,
        ];
    }
}
