<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_en' => rtrim(fake()->sentence(), '.').'?',
            'question_ar' => 'سؤال '.fake()->numberBetween(1, 999).'؟',
            'answer_en' => fake()->paragraph(),
            'answer_ar' => 'الإجابة.',
            'sort_order' => 0,
            'is_published' => true,
        ];
    }

    /**
     * Indicate that shoppers cannot see the question yet.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
