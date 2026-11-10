<?php

namespace App\Support;

use App\Enums\OrderChannel;
use App\Enums\PaymentMethod;
use App\Models\DeliveryZone;
use Carbon\CarbonImmutable;

/**
 * Who is ordering, where to deliver and how they pay, as entered at checkout or by the team.
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
        public OrderChannel $channel = OrderChannel::Web,
        // Dirhams. Set by the team on orders entered by hand; null charges the area's usual fee.
        public ?int $deliveryFee = null,
        public ?string $couponCode = null,
        // The signed-in shopper's account, so the order shows in "My orders".
        public ?int $userId = null,
    ) {}
}
