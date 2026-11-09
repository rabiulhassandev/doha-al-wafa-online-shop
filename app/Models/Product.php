<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'size_guide_id',
    'name_en',
    'name_ar',
    'slug',
    'code',
    'description_en',
    'description_ar',
    'details_en',
    'details_ar',
    'price',
    'compare_at_price',
    'status',
    'is_featured',
    'meta_title_en',
    'meta_title_ar',
    'meta_description_en',
    'meta_description_ar',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'is_featured' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Register model event listeners.
     */
    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if ($product->status === ProductStatus::Active && $product->published_at === null) {
                $product->published_at = now();
            }
        });

        // Delete colors one by one so their photos are removed from storage too.
        static::deleting(function (Product $product): void {
            $product->colors()->get()->each->delete();
        });
    }

    /**
     * Get the product's category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the product's size guide.
     *
     * @return BelongsTo<SizeGuide, $this>
     */
    public function sizeGuide(): BelongsTo
    {
        return $this->belongsTo(SizeGuide::class);
    }

    /**
     * Get the collections the product appears in.
     *
     * @return BelongsToMany<Collection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    /**
     * Get the colors the product comes in, in display order.
     *
     * @return HasMany<ProductColor, $this>
     */
    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get every color and size combination of the product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * List what still needs doing before shoppers can order the product.
     *
     * Expects the colors (with images) and variants relations to be loaded.
     *
     * @return list<string>
     */
    public function setupIssues(): array
    {
        $activeColors = $this->colors->where('is_active', true);

        if ($activeColors->isEmpty()) {
            return ['Add at least one color.'];
        }

        $issues = $activeColors
            ->filter(fn (ProductColor $productColor): bool => $productColor->images->isEmpty())
            ->map(fn (ProductColor $productColor): string => "Add photos for {$productColor->nameEn()}.")
            ->values()
            ->all();

        if ($this->variants->where('is_active', true)->isEmpty()) {
            $issues[] = 'Choose sizes and enter stock.';
        }

        return $issues;
    }
}
