<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+974'.fake()->numerify('5#######'),
            'email' => null,
            'message' => fake()->paragraph(),
            'locale' => 'en',
            'ip_address' => null,
            'read_at' => null,
        ];
    }

    /**
     * Indicate that someone on the team opened the message.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
