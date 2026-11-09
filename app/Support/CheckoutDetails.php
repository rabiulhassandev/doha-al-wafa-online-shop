<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Models\DeliveryZone;
use Carbon\CarbonImmutable;

/**
 * Who is ordering, where to deliver and how they pay, as entered at checkout.
 */
final readonly class CheckoutDetails
{
    public function __construct(
        public string $name,
        public QatarPhone $phone,
        public ?string $email,
        public DeliveryZone $deliveryZone,
        public string $zoneNumber,
        public string $streetNumber,
        public string $buildingNumber,
        public ?string $unit,
        public ?string $landmark,
        public ?string $latitude,
        public ?string $longitude,
        public ?CarbonImmutable $preferredDate,
        public ?string $note,
        public PaymentMethod $paymentMethod,
        public string $locale,
        public ?string $checkoutToken = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}
}
