<?php

namespace App\Http\Requests\Storefront;

use App\Enums\PaymentMethod;
use App\Models\DeliveryZone;
use App\Rules\QatarPhoneNumber;
use App\Services\CartService;
use App\Services\Payments\PaymentGateways;
use App\Services\Settings;
use App\Support\CheckoutDetails;
use App\Support\Digits;
use App\Support\QatarPhone;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Fields that hold numbers from the blue address plate.
     */
    private const PLATE_NUMBERS = ['zone_number', 'street_number', 'building_number'];

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
        foreach ([...self::PLATE_NUMBERS, 'phone'] as $field) {
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
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', QatarPhoneNumber::mobile()],
            'email' => ['nullable', 'string', 'email', 'max:190'],
            'delivery_zone_id' => ['required', 'integer', Rule::exists('delivery_zones', 'id')->where('is_active', true)],
            'zone_number' => ['required', 'regex:/^\d{1,4}$/'],
            'street_number' => ['required', 'regex:/^\d{1,4}$/'],
            'building_number' => ['required', 'regex:/^\d{1,4}$/'],
            'unit' => ['nullable', 'string', 'max:60'],
            'landmark' => ['nullable', 'string', 'max:200'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'preferred_delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)->only(app(PaymentGateways::class)->enabledMethods())],
            'expected_subtotal' => ['required', 'integer', 'min:0'],
            'checkout_token' => ['required', 'uuid'],
            // Hidden from people; bots that fill every field are turned away.
            'website' => ['prohibited'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $date = $this->input('preferred_delivery_date');

                if ($validator->errors()->hasAny(['delivery_zone_id', 'preferred_delivery_date']) || ! filled($date) || ! $this->preferredDateEnabled()) {
                    return;
                }

                $choosable = array_map(
                    fn (CarbonImmutable $day): string => $day->toDateString(),
                    $this->deliveryZone()->choosableDeliveryDays(CarbonImmutable::now(), app(Settings::class)->deliveryDaysOff()),
                );

                if (! in_array($date, $choosable, true)) {
                    $validator->errors()->add('preferred_delivery_date', __('Choose one of the dates shown.'));
                }
            },
        ];
    }

    /**
     * Get the error messages shoppers see, in their language.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $tooLong = __('Please shorten this to :max characters.');

        return [
            'name.required' => __('Enter your full name.'),
            'name.max' => $tooLong,
            'phone' => __('Enter a Qatar mobile number with 8 digits.'),
            'email' => __('Enter a valid email address, or leave it empty.'),
            'delivery_zone_id' => __('Choose your area.'),
            'zone_number' => __('Enter the zone number from the blue plate.'),
            'street_number' => __('Enter the street number from the blue plate.'),
            'building_number' => __('Enter the building number from the blue plate.'),
            'unit.max' => $tooLong,
            'landmark.max' => $tooLong,
            'latitude' => __('We could not read your location. Please try again.'),
            'longitude' => __('We could not read your location. Please try again.'),
            'preferred_delivery_date' => __('Choose one of the dates shown.'),
            'note.max' => $tooLong,
            'payment_method' => __('Choose how you will pay.'),
            'expected_subtotal' => __('Something went wrong. Please refresh the page and try again.'),
            'checkout_token' => __('Something went wrong. Please refresh the page and try again.'),
            'website' => __('Something went wrong. Please refresh the page and try again.'),
        ];
    }

    /**
     * Get the checked checkout details.
     */
    public function details(): CheckoutDetails
    {
        $preferredDate = $this->preferredDateEnabled() ? $this->validated('preferred_delivery_date') : null;

        return new CheckoutDetails(
            name: $this->validated('name'),
            phone: QatarPhone::tryParse($this->validated('phone')),
            email: $this->validated('email'),
            deliveryZone: $this->deliveryZone(),
            zoneNumber: $this->plateNumber('zone_number'),
            streetNumber: $this->plateNumber('street_number'),
            buildingNumber: $this->plateNumber('building_number'),
            unit: $this->validated('unit'),
            landmark: $this->validated('landmark'),
            latitude: $this->coordinate('latitude'),
            longitude: $this->coordinate('longitude'),
            preferredDate: $preferredDate === null ? null : CarbonImmutable::createFromFormat('!Y-m-d', $preferredDate),
            note: $this->validated('note'),
            paymentMethod: PaymentMethod::from($this->validated('payment_method')),
            locale: app()->getLocale(),
            checkoutToken: $this->validated('checkout_token'),
            ipAddress: $this->ip(),
            userAgent: Str::limit((string) $this->userAgent(), 250, ''),
            couponCode: $this->session()->get(CartService::COUPON_SESSION_KEY),
        );
    }

    private function deliveryZone(): DeliveryZone
    {
        return DeliveryZone::query()->active()->findOrFail($this->input('delivery_zone_id'));
    }

    private function preferredDateEnabled(): bool
    {
        return (bool) app(Settings::class)->get('preferred_date_enabled');
    }

    /**
     * Get a blue plate number without leading zeros, e.g. "045" becomes "45".
     */
    private function plateNumber(string $field): string
    {
        return (string) (int) $this->validated($field);
    }

    /**
     * Get a map coordinate rounded to about 10 cm.
     */
    private function coordinate(string $field): ?string
    {
        $value = $this->validated($field);

        return $value === null ? null : number_format((float) $value, 6, '.', '');
    }
}
