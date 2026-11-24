<?php

namespace Database\Factories;

use App\Models\ProductColor;
use App\Models\ProductVideo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVideo>
 */
class ProductVideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_color_id' => ProductColor::factory(),
            'path' => 'products/videos/'.Str::uuid().'.mp4',
            'mime_type' => 'video/mp4',
        ];
    }
}
