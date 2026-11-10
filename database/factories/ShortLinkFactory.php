<?php

namespace Database\Factories;

use App\Models\ShortLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShortLink>
 */
class ShortLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??????'),
            'label' => 'Instagram bio',
            'destination' => '/ar',
            'utm_source' => 'instagram',
            'utm_medium' => 'social',
            'utm_campaign' => null,
            'utm_content' => null,
            'coupon_code' => null,
            'is_active' => true,
        ];
    }
}
