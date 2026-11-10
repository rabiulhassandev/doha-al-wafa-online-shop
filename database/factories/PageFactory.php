<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => 'page-'.fake()->unique()->numberBetween(1, 99999),
            'title_en' => fake()->sentence(3),
            'title_ar' => 'صفحة '.fake()->numberBetween(1, 999),
            'body_en' => fake()->paragraph(),
            'body_ar' => 'نص الصفحة.',
            'sort_order' => 0,
            'is_published' => true,
        ];
    }

    /**
     * Indicate that shoppers cannot see the page yet.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
