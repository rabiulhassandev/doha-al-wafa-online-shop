<?php

namespace Database\Factories;

use App\Enums\ImageFocus;
use App\Models\ProductColor;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state. No files are written.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_color_id' => ProductColor::factory(),
            'directory' => 'products/test/'.Str::lower(Str::random(12)),
            'widths' => [1200, 800, 400],
            'focus' => ImageFocus::Center,
            'sort_order' => 0,
        ];
    }
}
