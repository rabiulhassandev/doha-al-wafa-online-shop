<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => PaymentMethod::CashOnDelivery,
            'provider' => null,
            'status' => PaymentStatus::Pending,
            'amount' => 20500,
            'currency' => 'QAR',
            'reference' => null,
            'meta' => null,
            'paid_at' => null,
        ];
    }
}
