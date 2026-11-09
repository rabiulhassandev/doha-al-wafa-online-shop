<?php

namespace Database\Factories;

use App\Enums\SizeGroup;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Size>
 */
class SizeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $length = (string) fake()->unique()->numberBetween(40, 70);

        return [
            'name_en' => $length,
            'name_ar' => null,
            'slug' => "length-{$length}",
            'group' => SizeGroup::Length,
            'sort_order' => (int) $length,
        ];
    }

    /**
     * Indicate that the size is a letter size, such as M.
     */
    public function letter(string $name = 'M'): static
    {
        return $this->state(fn (array $attributes) => [
            'name_en' => $name,
            'slug' => strtolower($name),
            'group' => SizeGroup::Letter,
        ]);
    }
}
