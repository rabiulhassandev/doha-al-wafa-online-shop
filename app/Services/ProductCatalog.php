<?php

namespace App\Services;

use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Support\ProductFilters;
use App\Support\SearchText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds the product queries behind every storefront listing.
 */
class ProductCatalog
{
    /**
     * Products shoppers can see, with everything a product card needs loaded.
     *
     * @return Builder<Product>
     */
    public function listed(): Builder
    {
        return Product::query()
            ->listed()
            ->with([
                'colors' => fn ($query) => $query->listed(),
                'colors.color',
                'colors.images',
                'colors.variants' => fn ($query) => $query->where('is_active', true),
            ])
            ->withExists(['variants as in_stock' => fn (Builder $query) => $query
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->whereHas('productColor', fn (Builder $query) => $query->listed())]);
    }

    /**
     * Match products by name or code, ignoring Arabic spelling variations.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function search(Builder $query, string $term): Builder
    {
        $words = array_filter(explode(' ', SearchText::normalize($term)));

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';

            $query->where(fn (Builder $query) => $query
                ->where('search_text', 'like', $like)
                ->orWhereHas('category', fn (Builder $query) => $query->where('name_en', 'like', $like)->orWhere('name_ar', 'like', $like))
                ->orWhereHas('colors.color', fn (Builder $query) => $query->where('name_en', 'like', $like)->orWhere('name_ar', 'like', $like)));
        }

        return $query;
    }

    /**
     * Apply the shopper's filters and sort order.
     *
     * Color, size and "in stock" filters must all match the same color and size, so "Navy" and "56"
     * only finds products sold in Navy size 56. In-stock products always come first.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function filter(Builder $query, ProductFilters $filters): Builder
    {
        if ($filters->filtersVariants()) {
            $query->whereHas('variants', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereHas('productColor', fn (Builder $query) => $query
                    ->listed()
                    ->when($filters->colors !== [], fn (Builder $query) => $query->whereHas('color', fn (Builder $query) => $query->whereIn('slug', $filters->colors))))
                ->when($filters->sizes !== [], fn (Builder $query) => $query->whereHas('size', fn (Builder $query) => $query->whereIn('slug', $filters->sizes)))
                ->when($filters->inStockOnly, fn (Builder $query) => $query->where('stock', '>', 0)));
        }

        $query
            ->when($filters->minPrice !== null, fn (Builder $query) => $query->where('price', '>=', $filters->minPrice))
            ->when($filters->maxPrice !== null, fn (Builder $query) => $query->where('price', '<=', $filters->maxPrice))
            ->orderByDesc('in_stock');

        match ($filters->sort) {
            'newest' => $query->orderByDesc('published_at'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->orderByDesc('is_featured')->orderByDesc('published_at'),
        };

        return $query->orderByDesc('id');
    }

    /**
     * Get the colors and sizes worth offering as filters for a set of products.
     *
     * @param  Builder<Product>  $query  the listing before shopper filters
     * @return array{colors: Collection<int, Color>, sizes: Collection<int, Size>}
     */
    public function facets(Builder $query): array
    {
        $productIds = (clone $query)->setEagerLoads([])->reorder()->select('products.id');

        $offeredVariants = fn (Builder $variants) => $variants
            ->where('is_active', true)
            ->whereIn('product_id', $productIds)
            ->whereHas('productColor', fn (Builder $query) => $query->listed());

        return [
            'colors' => Color::query()
                ->whereHas('productColors', fn (Builder $query) => $query
                    ->listed()
                    ->whereIn('product_id', $productIds))
                ->ordered()
                ->get(),
            'sizes' => Size::query()
                ->whereHas('variants', $offeredVariants)
                ->ordered()
                ->get(),
        ];
    }

    /**
     * Get other products from the same category, in-stock ones first.
     *
     * @return Collection<int, Product>
     */
    public function related(Product $product, int $limit = 8): Collection
    {
        return $this->listed()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->orderByDesc('in_stock')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Get listed products by ID, keeping the given order. Unknown or hidden IDs are skipped.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    public function byIds(array $ids): Collection
    {
        $products = $this->listed()->whereKey($ids)->get()->keyBy('id');

        return new Collection(array_values(array_filter(array_map(fn (int $id): ?Product => $products->get($id), $ids))));
    }
}
