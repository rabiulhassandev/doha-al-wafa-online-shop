<?php

namespace App\Http\Requests\Admin;

use App\Enums\ImageFocus;
use App\Models\ProductColor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductImageRequest extends FormRequest
{
    public const MAX_IMAGES_PER_COLOR = 12;

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
            'images' => ['required', 'array', 'min:1', 'max:'.self::MAX_IMAGES_PER_COLOR],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360', 'dimensions:min_width=500,min_height=625'],
            'focus' => ['required', Rule::enum(ImageFocus::class)],
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
                if ($validator->errors()->has('images')) {
                    return;
                }

                $total = $this->productColor()->images()->count() + count($this->file('images', []));

                if ($total > self::MAX_IMAGES_PER_COLOR) {
                    $validator->errors()->add('images', 'Each color can have up to '.self::MAX_IMAGES_PER_COLOR.' photos.');
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
            'images.required' => 'Choose at least one photo.',
            'images.*.image' => 'Photos must be JPG, PNG or WebP images.',
            'images.*.mimes' => 'Photos must be JPG, PNG or WebP images.',
            'images.*.max' => 'Each photo must be smaller than 15 MB.',
            'images.*.dimensions' => 'Photos must be at least 500 × 625 pixels.',
        ];
    }

    /**
     * Get the product color receiving the photos.
     */
    private function productColor(): ProductColor
    {
        return $this->route('color');
    }
}
