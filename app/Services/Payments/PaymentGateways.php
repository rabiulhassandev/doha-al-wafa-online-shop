<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * The payment methods offered at checkout, from the "shop.payment_gateways" config.
 */
class PaymentGateways
{
    public function __construct(private readonly Container $container) {}

    /**
     * Get the payment methods shoppers can choose, in the configured order.
     *
     * @return list<PaymentMethod>
     */
    public function enabledMethods(): array
    {
        return array_map(
            fn (string $method): PaymentMethod => PaymentMethod::from($method),
            array_keys(config('shop.payment_gateways')),
        );
    }

    /**
     * Get the gateway that handles a payment method.
     */
    public function for(PaymentMethod $method): PaymentGateway
    {
        $gateway = config("shop.payment_gateways.{$method->value}")
            ?? throw new InvalidArgumentException("No payment gateway is set up for [{$method->value}].");

        return $this->container->make($gateway);
    }
}
