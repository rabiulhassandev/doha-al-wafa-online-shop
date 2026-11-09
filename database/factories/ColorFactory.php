<?php

namespace Database\Factories;

use App\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Color>
 */
class ColorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->colorName());

        return [
            'name_en' => $name,
            'name_ar' => 'لون '.fake()->unique()->numberBetween(1, 99999),
            'slug' => Str::slug($name),
            'hex' => Str::lower(fake()->hexColor()),
            'sort_order' => 0,
        ];
    }
}
