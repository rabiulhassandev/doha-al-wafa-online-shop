<?php

namespace App\Http\Requests\Storefront\Account;

use App\Enums\UserRole;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
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
            'email' => __('Enter a valid email address.'),
            'password' => __('Enter your password.'),
        ];
    }

    /**
     * Sign the shopper in, slowing down repeated failures for the same email and connection.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            event(new Lockout($this));

            throw ValidationException::withMessages([
                'email' => __('Too many attempts. Please try again in :minutes minutes.', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        if (! Auth::attempt([
            'email' => Str::lower($this->string('email')),
            'password' => $this->string('password'),
            'role' => UserRole::Customer->value,
        ], $this->boolean('remember'))) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'email' => __('The email or password is not right.'),
            ]);
        }

        RateLimiter::clear($key);
    }

    private function throttleKey(): string
    {
        return 'account-login|'.Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
