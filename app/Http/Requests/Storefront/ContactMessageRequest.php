<?php

namespace App\Http\Requests\Storefront;

use App\Rules\QatarPhoneNumber;
use App\Support\Digits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept phone numbers typed on Arabic keyboards.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => Digits::toWestern($this->input('phone'))]);
        }
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
            'phone' => ['nullable', 'required_without:email', 'string', new QatarPhoneNumber],
            'email' => ['nullable', 'string', 'email', 'max:190'],
            'message' => ['required', 'string', 'max:2000'],
            // Hidden from people; bots that fill every field are turned away.
            'website' => ['prohibited'],
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
            'phone.required_without' => __('Enter your phone number or your email so we can reply.'),
            'phone' => __('Enter a Qatar phone number with 8 digits.'),
            'email' => __('Enter a valid email address, or leave it empty.'),
            'message.required' => __('Write your message.'),
            'message.max' => __('Please shorten this to :max characters.'),
            'website' => __('Something went wrong. Please refresh the page and try again.'),
        ];
    }
}
