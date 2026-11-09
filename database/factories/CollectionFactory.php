<?php

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name_en' => $name,
            'name_ar' => 'مجموعة '.fake()->unique()->numberBetween(1, 99999),
            'slug' => Str::slug($name),
            'description_en' => null,
            'description_ar' => null,
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the collection is hidden from the store.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the collection starts in the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeeks(3),
        ]);
    }

    /**
     * Indicate that the collection has already ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subWeeks(3),
            'ends_at' => now()->subWeek(),
        ]);
    }
}
