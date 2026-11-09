<?php

namespace Database\Factories;

use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
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
            'product_id' => fn (array $attributes): int => ProductColor::query()->findOrFail($attributes['product_color_id'])->product_id,
            'size_id' => Size::factory(),
            'sku' => 'SKU-'.Str::upper(Str::random(10)),
            'price' => null,
            'compare_at_price' => null,
            'stock' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate how many are in stock.
     */
    public function inStock(int $quantity = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $quantity,
        ]);
    }

    /**
     * Indicate that this color and size is no longer sold.
     */
    public function notOffered(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
