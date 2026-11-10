<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SAVE'.fake()->unique()->numberBetween(10, 9999),
            'description' => null,
            'type' => CouponType::Percent,
            'value' => 10,
            'min_subtotal' => null,
            'starts_at' => null,
            'ends_at' => null,
            'max_uses' => null,
            'max_uses_per_phone' => null,
            'first_order_only' => false,
            'is_active' => true,
        ];
    }

    /**
     * Take a fixed amount, in dirhams, off the bag.
     */
    public function fixed(int $dirhams): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Fixed,
            'value' => $dirhams,
        ]);
    }

    /**
     * Take off the delivery fee.
     */
    public function freeDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::FreeDelivery,
            'value' => 0,
        ]);
    }
}
