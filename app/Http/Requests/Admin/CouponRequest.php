<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Store codes in capitals, as shoppers see them.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => Coupon::normalizeCode($this->input('code'))]);
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
            'code' => ['required', 'string', 'regex:/^[A-Z0-9-]{3,30}$/', Rule::unique('coupons', 'code')->ignore($this->route('coupon'))],
            'description' => ['nullable', 'string', 'max:200'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'percent' => ['nullable', 'required_if:type,'.CouponType::Percent->value, 'integer', 'between:1,100'],
            'amount' => ['nullable', 'required_if:type,'.CouponType::Fixed->value, 'decimal:0,2', 'min:1', 'max:100000'],
            'min_subtotal' => ['nullable', 'decimal:0,2', 'min:0', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($this->filled('starts_at'), 'after:starts_at')],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'max_uses_per_phone' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'first_order_only' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
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
            'code.regex' => 'Use 3 to 30 letters, digits or dashes, such as EID10 or SARA-15.',
            'code.unique' => 'Another coupon already uses this code.',
            'percent.required_if' => 'Enter the percentage off.',
            'amount.required_if' => 'Enter the amount off.',
            'ends_at.after' => 'The end must be after the start.',
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
            'min_subtotal' => 'minimum spend',
            'starts_at' => 'start',
            'ends_at' => 'end',
            'max_uses' => 'total uses',
            'max_uses_per_phone' => 'uses per customer',
        ];
    }

    /**
     * Get the validated coupon attributes, with amounts in dirhams.
     *
     * @return array<string, mixed>
     */
    public function couponAttributes(): array
    {
        $type = CouponType::from($this->validated('type'));
        $minSubtotal = $this->validated('min_subtotal');

        return [
            'code' => $this->validated('code'),
            'description' => $this->validated('description'),
            'type' => $type,
            'value' => match ($type) {
                CouponType::Percent => (int) $this->validated('percent'),
                CouponType::Fixed => Money::toDirhams($this->validated('amount')),
                CouponType::FreeDelivery => 0,
            },
            'min_subtotal' => $minSubtotal === null ? null : Money::toDirhams($minSubtotal),
            'starts_at' => $this->dateTime('starts_at'),
            'ends_at' => $this->dateTime('ends_at'),
            'max_uses' => $this->validated('max_uses') === null ? null : (int) $this->validated('max_uses'),
            'max_uses_per_phone' => $this->validated('max_uses_per_phone') === null ? null : (int) $this->validated('max_uses_per_phone'),
            'first_order_only' => $this->boolean('first_order_only'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    private function dateTime(string $field): ?CarbonImmutable
    {
        $value = $this->validated($field);

        return $value === null ? null : CarbonImmutable::parse($value);
    }
}
