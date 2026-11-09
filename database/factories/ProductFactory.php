<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'size_guide_id' => null,
            'name_en' => $name,
            'name_ar' => 'جلابية '.fake()->unique()->numberBetween(1, 99999),
            'slug' => Str::slug($name),
            'code' => Str::upper(fake()->unique()->bothify('P####')),
            'price' => fake()->numberBetween(80, 600) * 100,
            'compare_at_price' => null,
            'status' => ProductStatus::Draft,
            'is_featured' => false,
        ];
    }

    /**
     * Indicate that the product is shown in the store.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Active,
        ]);
    }

    /**
     * Indicate that the product is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Archived,
        ]);
    }
}
