<?php

namespace Database\Factories;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'channel' => OrderChannel::Web,
            'status' => OrderStatus::PendingConfirmation,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'currency' => 'QAR',
            'subtotal' => 18500,
            'discount_total' => 0,
            'delivery_fee' => 2000,
            'tax_total' => 0,
            'grand_total' => 20500,
            'customer_name' => fn (array $attributes): string => Customer::query()->find($attributes['customer_id'])?->name ?? fake()->name(),
            'customer_phone' => fn (array $attributes): string => Customer::query()->find($attributes['customer_id'])?->phone ?? '+97455551234',
            'customer_email' => null,
            'delivery_zone_id' => null,
            'delivery_zone_name_en' => 'Doha',
            'delivery_zone_name_ar' => 'الدوحة',
            'zone_number' => '45',
            'street_number' => '330',
            'building_number' => '12',
            'unit' => null,
            'landmark' => null,
            'latitude' => null,
            'longitude' => null,
            'preferred_delivery_date' => null,
            'delivery_estimate_from' => now()->addDay()->toDateString(),
            'delivery_estimate_to' => now()->addDays(3)->toDateString(),
            'customer_note' => null,
            'locale' => 'en',
            'placed_at' => now(),
        ];
    }

    /**
     * Give the order its number once it has an ID, as placing an order does.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Order $order): void {
            $order->number ??= Order::numberFor($order->id);
            $order->save();
        });
    }
}
