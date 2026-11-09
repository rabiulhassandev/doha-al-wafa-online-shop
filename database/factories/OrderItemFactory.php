<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'product_variant_id' => null,
            'product_name_en' => 'Classic crepe jelabiya',
            'product_name_ar' => 'جلابية كريب كلاسيكية',
            'color_name_en' => 'Navy',
            'color_name_ar' => 'كحلي',
            'color_hex' => '#1f2a44',
            'size_name_en' => '56',
            'size_name_ar' => null,
            'sku' => 'JLB-'.fake()->unique()->numerify('####'),
            'image_path' => null,
            'unit_price' => 18500,
            'compare_at_price' => null,
            'quantity' => 1,
            'line_total' => fn (array $attributes): int => $attributes['unit_price'] * $attributes['quantity'],
        ];
    }
}
