<?php

namespace Database\Factories;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscriber>
 */
class SubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => '+974'.fake()->unique()->numerify('5#######'),
            'email' => null,
            'locale' => 'en',
            'source' => 'footer',
            'ip_address' => null,
        ];
    }
}
