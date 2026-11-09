<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateStaffRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->member())],
            'role' => ['required', Rule::enum(UserRole::class)->only(UserRole::staffRoles())],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
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
                if ($validator->errors()->has('role')) {
                    return;
                }

                // Owners can't demote themselves, so the store always keeps at least one owner.
                if ($this->member()->is($this->user()) && $this->enum('role', UserRole::class) !== $this->member()->role) {
                    $validator->errors()->add('role', "You can't change your own role.");
                }
            },
        ];
    }

    /**
     * Get the team member being updated.
     */
    private function member(): User
    {
        return $this->route('staff');
    }
}
