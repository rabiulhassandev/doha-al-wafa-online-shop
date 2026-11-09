<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderChannel;
use App\Enums\PaymentMethod;
use App\Models\DeliveryZone;
use App\Rules\QatarPhoneNumber;
use App\Support\CheckoutDetails;
use App\Support\Digits;
use App\Support\Money;
use App\Support\QatarPhone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An order the team takes on WhatsApp, Instagram or the phone and enters by hand.
 */
class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept numbers typed on Arabic keyboards.
     */
    protected function prepareForValidation(): void
    {
        foreach (['zone_number', 'street_number', 'building_number', 'phone'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => Digits::toWestern($this->input($field))]);
            }
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
            'channel' => ['required', Rule::enum(OrderChannel::class)->except([OrderChannel::Web])],
            'locale' => ['required', Rule::in(array_keys(config('shop.locales')))],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', QatarPhoneNumber::mobile()],
            'email' => ['nullable', 'string', 'email', 'max:190'],
            'delivery_zone_id' => ['required', 'integer', Rule::exists('delivery_zones', 'id')],
            'zone_number' => ['required', 'regex:/^\d{1,4}$/'],
            'street_number' => ['required', 'regex:/^\d{1,4}$/'],
            'building_number' => ['required', 'regex:/^\d{1,4}$/'],
            'unit' => ['nullable', 'string', 'max:60'],
            'landmark' => ['nullable', 'string', 'max:200'],
            'preferred_delivery_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'delivery_fee' => ['nullable', 'decimal:0,2', 'min:0', 'max:10000'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.variant_id' => ['required', 'integer', 'distinct'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
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
            'lines.required' => 'Add at least one item.',
            'lines.*.variant_id.distinct' => 'Each color and size can only be listed once. Raise its quantity instead.',
            'zone_number.regex' => 'Use the zone number from the blue plate, in digits.',
            'street_number.regex' => 'Use the street number from the blue plate, in digits.',
            'building_number.regex' => 'Use the building number from the blue plate, in digits.',
            'preferred_delivery_date.after_or_equal' => 'The preferred delivery date cannot be in the past.',
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
            'locale' => 'customer’s language',
            'phone' => 'mobile number',
            'delivery_zone_id' => 'delivery area',
            'delivery_fee' => 'delivery fee',
            'customer_note' => 'customer note',
            'admin_note' => 'team note',
            'lines.*.quantity' => 'quantity',
        ];
    }

    /**
     * Get the pieces ordered, keyed by product variant ID.
     *
     * @return array<int, int>
     */
    public function quantities(): array
    {
        return collect($this->validated('lines'))
            ->mapWithKeys(fn (array $line): array => [(int) $line['variant_id'] => (int) $line['quantity']])
            ->all();
    }

    /**
     * Get the checked order details.
     */
    public function details(): CheckoutDetails
    {
        $deliveryFee = $this->validated('delivery_fee');
        $preferredDate = $this->validated('preferred_delivery_date');

        return new CheckoutDetails(
            name: $this->validated('name'),
            phone: QatarPhone::tryParse($this->validated('phone')),
            email: $this->validated('email'),
            deliveryZone: DeliveryZone::query()->findOrFail($this->validated('delivery_zone_id')),
            zoneNumber: (string) (int) $this->validated('zone_number'),
            streetNumber: (string) (int) $this->validated('street_number'),
            buildingNumber: (string) (int) $this->validated('building_number'),
            unit: $this->validated('unit'),
            landmark: $this->validated('landmark'),
            latitude: null,
            longitude: null,
            preferredDate: $preferredDate === null ? null : CarbonImmutable::createFromFormat('!Y-m-d', $preferredDate),
            note: $this->validated('customer_note'),
            paymentMethod: PaymentMethod::CashOnDelivery,
            locale: $this->validated('locale'),
            channel: OrderChannel::from($this->validated('channel')),
            deliveryFee: $deliveryFee === null ? null : Money::toDirhams($deliveryFee),
        );
    }
}
