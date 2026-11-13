<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\Translatable;
use App\Support\SearchText;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
    use HasFactory, Translatable;

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

            $product->search_text = SearchText::normalize("{$product->name_en} {$product->name_ar} {$product->code}");
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
     * Scope the query to products shoppers can see: active, with at least one listed color.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('status', ProductStatus::Active)
            ->whereHas('colors', fn (Builder $query) => $query->listed());
    }

    /**
     * Determine whether shoppers can see the product. Expects colors with images and variants loaded.
     */
    public function isListed(): bool
    {
        return $this->status === ProductStatus::Active && $this->listedColors()->isNotEmpty();
    }

    /**
     * Get the colors shoppers can choose. Expects colors with images and variants loaded.
     *
     * @return EloquentCollection<int, ProductColor>
     */
    public function listedColors(): EloquentCollection
    {
        return $this->colors->filter(fn (ProductColor $productColor): bool => $productColor->isListed())->values();
    }

    /**
     * Pick the color to show first: one the shopper filtered by, then the default color, then the first.
     *
     * @param  list<int>  $preferredColorIds  palette color IDs
     */
    public function displayColor(array $preferredColorIds = []): ?ProductColor
    {
        $listedColors = $this->listedColors();

        return $listedColors->first(fn (ProductColor $productColor): bool => in_array($productColor->color_id, $preferredColorIds, true))
            ?? $listedColors->firstWhere('is_default', true)
            ?? $listedColors->first();
    }

    /**
     * Get the lowest and highest price shoppers pay across the offered sizes, in dirhams.
     *
     * @return array{int, int}
     */
    public function priceRange(): array
    {
        $prices = $this->listedColors()
            ->flatMap(fn (ProductColor $productColor) => $productColor->offeredVariants())
            ->map(fn (ProductVariant $variant): int => $variant->price ?? $this->price);

        return $prices->isEmpty() ? [$this->price, $this->price] : [$prices->min(), $prices->max()];
    }

    /**
     * Determine whether any offered color and size is in stock.
     */
    public function isInStock(): bool
    {
        return $this->listedColors()->contains(fn (ProductColor $productColor): bool => $productColor->isInStock());
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
            return ['Set up product photos or enable a color.'];
        }

        $issues = $activeColors
            ->filter(fn (ProductColor $productColor): bool => $productColor->images->isEmpty())
            ->map(fn (ProductColor $productColor): string => $productColor->hasColor() ? "Add photos for {$productColor->nameEn()}." : 'Add product photos.')
            ->values()
            ->all();

        if ($this->variants->where('is_active', true)->isEmpty()) {
            $issues[] = 'Choose sizes and enter stock.';
        }

        return $issues;
    }
}
