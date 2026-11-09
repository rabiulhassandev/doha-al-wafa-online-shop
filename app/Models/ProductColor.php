<?php

namespace App\Models;

use Database\Factories\ProductColorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One color a product comes in, with its own photos and sizes.
 */
#[Fillable(['product_id', 'color_id', 'label_en', 'label_ar', 'sort_order', 'is_default', 'is_active'])]
class ProductColor extends Model
{
    /** @use HasFactory<ProductColorFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_default' => false,
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Register model event listeners.
     */
    protected static function booted(): void
    {
        // Delete images one by one so their files are removed from storage too.
        static::deleting(function (ProductColor $productColor): void {
            $productColor->images()->get()->each->delete();
        });
    }

    /**
     * Get the product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the palette color.
     *
     * @return BelongsTo<Color, $this>
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * Get the photos of the product in this color, in display order.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the sizes of the product in this color.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the English name shown for this color: the custom label, or the palette name.
     */
    public function nameEn(): string
    {
        return $this->label_en ?: $this->color->name_en;
    }
}
