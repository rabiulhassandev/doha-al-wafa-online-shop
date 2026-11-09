<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_en' => fake()->unique()->city(),
            'name_ar' => 'منطقة '.fake()->unique()->numberBetween(1, 9999),
            'fee' => 2000,
            'free_over' => null,
            'eta_min_days' => 1,
            'eta_max_days' => 3,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that deliveries to the area are paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
