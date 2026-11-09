<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state. No image files are written.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_en' => Str::title(fake()->words(3, true)),
            'title_ar' => 'عرض '.fake()->numberBetween(1, 999),
            'subtitle_en' => null,
            'subtitle_ar' => null,
            'button_label_en' => 'Shop now',
            'button_label_ar' => 'تسوقي الآن',
            'link' => 'shop',
            'image_path' => 'banners/'.Str::random(20).'.webp',
            'mobile_image_path' => null,
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the banner is switched off.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the banner starts in the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addWeek(),
        ]);
    }
}
