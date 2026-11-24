<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductVideoRequest extends FormRequest
{
    public const MAX_VIDEOS_PER_GALLERY = 3;

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
            'video' => ['required', 'file', 'mimes:mp4,webm', 'mimetypes:video/mp4,video/webm', 'extensions:mp4,webm', 'max:30720'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'video.required' => 'Choose a video to upload.',
            'video.mimes' => 'Videos must be MP4 or WebM files.',
            'video.mimetypes' => 'Videos must be MP4 or WebM files.',
            'video.extensions' => 'Videos must be MP4 or WebM files.',
            'video.max' => 'Each video must be 30 MB or smaller.',
        ];
    }
}
