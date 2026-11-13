<?php

namespace App\Models;

use Database\Factories\ProductColorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A product gallery and its sizes, optionally assigned to a palette color.
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
        return $this->displayName('en') ?? 'Product';
    }

    public function hasColor(): bool
    {
        return $this->color_id !== null;
    }

    /**
     * Get the name shoppers see: this product's custom label, or the palette color name.
     */
    public function displayName(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->hasColor() ? ($this->getAttribute("label_{$locale}") ?: $this->color->translated('name', $locale)) : null;
    }

    /**
     * Scope the query to colors shoppers can choose: shown, photographed and offered in a size.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('images')
            ->whereHas('variants', fn (Builder $query) => $query->where('is_active', true));
    }

    /**
     * Determine whether shoppers can choose this color. Expects images and variants loaded.
     */
    public function isListed(): bool
    {
        return $this->is_active && $this->images->isNotEmpty() && $this->offeredVariants()->isNotEmpty();
    }

    /**
     * Get the sizes this color is sold in. Expects variants loaded.
     *
     * @return EloquentCollection<int, ProductVariant>
     */
    public function offeredVariants(): EloquentCollection
    {
        return $this->variants->where('is_active', true)->values();
    }

    /**
     * Determine whether any offered size of this color is in stock. Expects variants loaded.
     */
    public function isInStock(): bool
    {
        return $this->offeredVariants()->contains(fn (ProductVariant $variant): bool => $variant->stock > 0);
    }
}
