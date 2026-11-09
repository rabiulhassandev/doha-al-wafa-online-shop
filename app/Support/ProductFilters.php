<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The shopper's filter and sort choices on a product listing, read safely from the query string.
 *
 * Colors and sizes accept "color=navy,olive" (shareable links) or "color[]=navy" (the filter form).
 */
final class ProductFilters
{
    public const SORTS = ['featured', 'newest', 'price_asc', 'price_desc'];

    private const MAX_PRICE_DIRHAMS = 10_000_000;

    /**
     * @param  list<string>  $colors  palette color slugs
     * @param  list<string>  $sizes  size slugs
     */
    public function __construct(
        public readonly array $colors = [],
        public readonly array $sizes = [],
        public readonly ?int $minPrice = null,
        public readonly ?int $maxPrice = null,
        public readonly bool $inStockOnly = false,
        public readonly string $sort = 'featured',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = (string) $request->query('sort');

        return new self(
            colors: self::slugList($request->query('color')),
            sizes: self::slugList($request->query('size')),
            minPrice: self::price($request->query('min')),
            maxPrice: self::price($request->query('max')),
            inStockOnly: $request->boolean('in_stock'),
            sort: in_array($sort, self::SORTS, true) ? $sort : 'featured',
        );
    }

    /**
     * Determine whether a filter applies to individual colors and sizes rather than the whole product.
     */
    public function filtersVariants(): bool
    {
        return $this->colors !== [] || $this->sizes !== [] || $this->inStockOnly;
    }

    /**
     * Count the filters in use, for the "Filters (2)" button.
     */
    public function activeCount(): int
    {
        return count($this->colors) + count($this->sizes)
            + ($this->minPrice !== null || $this->maxPrice !== null ? 1 : 0)
            + ($this->inStockOnly ? 1 : 0);
    }

    /**
     * Get the query string values for links, with lists joined by commas.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'color' => implode(',', $this->colors),
            'size' => implode(',', $this->sizes),
            'min' => Money::toInput($this->minPrice) ?? '',
            'max' => Money::toInput($this->maxPrice) ?? '',
            'in_stock' => $this->inStockOnly ? '1' : '',
            'sort' => $this->sort === 'featured' ? '' : $this->sort,
        ], fn (string $value): bool => $value !== '');
    }

    /**
     * Get a copy of the filters without one color or size, for "remove this filter" links.
     */
    public function without(string $type, string $slug): self
    {
        return new self(
            colors: $type === 'color' ? array_values(array_diff($this->colors, [$slug])) : $this->colors,
            sizes: $type === 'size' ? array_values(array_diff($this->sizes, [$slug])) : $this->sizes,
            minPrice: $type === 'price' ? null : $this->minPrice,
            maxPrice: $type === 'price' ? null : $this->maxPrice,
            inStockOnly: $type === 'in_stock' ? false : $this->inStockOnly,
            sort: $this->sort,
        );
    }

    /**
     * @return list<string>
     */
    private static function slugList(mixed $value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);

        return collect($items)
            ->filter(fn (mixed $item): bool => is_string($item) && preg_match('/^[a-z0-9-]{1,80}$/', $item) === 1)
            ->unique()
            ->take(30)
            ->values()
            ->all();
    }

    private static function price(mixed $value): ?int
    {
        if (! is_numeric($value) || (float) $value < 0) {
            return null;
        }

        return min(Money::toDirhams($value), self::MAX_PRICE_DIRHAMS);
    }
}
