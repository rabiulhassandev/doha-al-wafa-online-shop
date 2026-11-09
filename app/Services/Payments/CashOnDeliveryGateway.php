<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;

/**
 * The customer pays the driver in cash. The payment waits until the team marks the cash as collected.
 */
class CashOnDeliveryGateway implements PaymentGateway
{
    public function createPayment(Order $order): Payment
    {
        return $order->payments()->create([
            'method' => PaymentMethod::CashOnDelivery,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);
    }

    public function paymentUrl(Payment $payment): ?string
    {
        return null;
    }
}
