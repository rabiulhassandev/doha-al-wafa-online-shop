<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

/**
 * Handles one way of paying. Online gateways added later implement this too, with no change to orders.
 */
interface PaymentGateway
{
    /**
     * Record the payment expected for a newly placed order. Runs inside the order's database transaction.
     */
    public function createPayment(Order $order): Payment;

    /**
     * Get the page where the shopper pays, or null when nothing is paid online. Runs once the order is saved.
     */
    public function paymentUrl(Payment $payment): ?string;
}
