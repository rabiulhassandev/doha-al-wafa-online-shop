<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One color and size in an order, with a copy of its names, SKU, photo and prices as sold.
 */
#[Fillable([
    'product_id', 'product_variant_id',
    'product_name_en', 'product_name_ar', 'color_name_en', 'color_name_ar', 'color_hex',
    'size_name_en', 'size_name_ar', 'sku', 'image_path',
    'unit_price', 'compare_at_price', 'quantity', 'line_total',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, Translatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'compare_at_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * Get the order.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product, while it still exists.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the color and size sold, while it still exists.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the URL of the photo copied when the order was placed.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk(config('shop.media_disk'))->url($this->image_path) : null;
    }
}
