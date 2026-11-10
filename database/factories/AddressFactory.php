<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Home',
            'delivery_zone_id' => DeliveryZone::factory(),
            'zone_number' => (string) fake()->numberBetween(1, 98),
            'street_number' => (string) fake()->numberBetween(1, 999),
            'building_number' => (string) fake()->numberBetween(1, 200),
            'unit' => null,
            'landmark' => null,
            'is_default' => false,
        ];
    }

    /**
     * Indicate that checkout fills in this address first.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
