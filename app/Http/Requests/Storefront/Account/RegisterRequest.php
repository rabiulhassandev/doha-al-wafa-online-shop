<?php

namespace App\Http\Requests\Storefront\Account;

use App\Rules\QatarPhoneNumber;
use App\Support\Digits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Shortest password customers may choose.
     */
    public const MIN_PASSWORD_LENGTH = 8;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Compare emails without case, and accept numbers typed on Arabic keyboards.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => Digits::toWestern((string) $this->input('phone')),
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', QatarPhoneNumber::mobile()],
            'password' => ['required', 'string', 'min:'.self::MIN_PASSWORD_LENGTH, 'max:100', 'confirmed'],
        ];
    }

    /**
     * Get the error messages shoppers see, in their language.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Enter your name.'),
            'name.max' => __('Please shorten this to :max characters.'),
            'email.unique' => __('An account already uses this email. Sign in instead.'),
            'email' => __('Enter a valid email address.'),
            'phone' => __('Enter a Qatar mobile number with 8 digits.'),
            'password.confirmed' => __('The two passwords do not match.'),
            'password' => __('Choose a password of at least :min characters.', ['min' => self::MIN_PASSWORD_LENGTH]),
        ];
    }
}
