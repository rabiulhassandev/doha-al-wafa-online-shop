<?php

namespace Database\Factories;

use App\Models\SizeGuide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SizeGuide>
 */
class SizeGuideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'columns' => [
                ['en' => 'Length', 'ar' => 'الطول'],
                ['en' => 'Chest', 'ar' => 'الصدر'],
            ],
            'rows' => [
                ['size' => '54', 'values' => [137.0, 112.0]],
                ['size' => '56', 'values' => [142.0, 116.0]],
            ],
            'notes_en' => null,
            'notes_ar' => null,
        ];
    }
}
