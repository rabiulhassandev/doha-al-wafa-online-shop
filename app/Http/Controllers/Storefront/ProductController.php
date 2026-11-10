<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Services\ProductCatalog;
use App\Services\Settings;
use App\Services\Tracking;
use App\Support\DeliveryEstimate;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly Settings $settings) {}

    /**
     * Show a product page. "?color=navy&size=56" opens it in that color and size.
     *
     * Team members can preview products that are not active yet.
     */
    public function show(Request $request, Product $product, ProductCatalog $catalog, Tracking $tracking): View
    {
        $product->load([
            'category.parent',
            'sizeGuide',
            'colors.color',
            'colors.images',
            'colors.variants' => fn ($query) => $query->where('is_active', true)->with('size'),
        ]);

        $colors = $product->listedColors();
        $isPreview = $product->status !== ProductStatus::Active;

        abort_if($colors->isEmpty(), 404);
        abort_if($isPreview && ! $request->user()?->can('manage-catalog'), 404);

        $selectedColor = $colors->first(fn (ProductColor $productColor): bool => $productColor->color->slug === $request->query('color'))
            ?? $product->displayColor();
        $sizes = $this->offeredSizes($colors);
        $requestedSize = (string) $request->query('size');
        $selectedSizeSlug = $selectedColor->offeredVariants()->contains(fn (ProductVariant $variant): bool => $variant->size->slug === $requestedSize)
            ? $requestedSize
            : null;

        $priceRange = $product->priceRange();

        return view('storefront.products.show', [
            'product' => $product,
            'colors' => $colors,
            'sizes' => $sizes,
            'selectedColor' => $selectedColor,
            'isPreview' => $isPreview,
            'priceRange' => $priceRange,
            'pageData' => $this->pageData($product, $colors, $sizes, $selectedColor, $selectedSizeSlug, (int) $this->settings->get('low_stock_threshold')),
            'related' => $catalog->related($product),
            'deliveryWindow' => $this->deliveryWindow(),
            'breadcrumbs' => $this->breadcrumbs($product),
            'trackingEvent' => $tracking->isEnabled()
                ? $tracking->viewContent((string) $product->translated('name'), $selectedColor->offeredVariants(), $priceRange[0])
                : null,
        ]);
    }

    /**
     * Get the earliest and latest delivery dates across the active delivery areas, if there are any.
     *
     * @return array{CarbonImmutable, CarbonImmutable}|null
     */
    private function deliveryWindow(): ?array
    {
        $zones = DeliveryZone::query()->active();
        $fastest = (clone $zones)->min('eta_min_days');
        $slowest = (clone $zones)->max('eta_max_days');

        if ($fastest === null) {
            return null;
        }

        return DeliveryEstimate::between(
            CarbonImmutable::now(),
            (int) $fastest,
            (int) $slowest,
            $this->settings->deliveryDaysOff(),
        );
    }

    /**
     * Get every size sold in any listed color, smallest first.
     *
     * @param  Collection<int, ProductColor>  $colors
     * @return Collection<int, Size>
     */
    private function offeredSizes(Collection $colors): Collection
    {
        return $colors
            ->flatMap(fn (ProductColor $productColor) => $productColor->offeredVariants()->pluck('size'))
            ->unique('id')
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->values()
            ->pipe(fn ($sizes) => new Collection($sizes->all()));
    }

    /**
     * Build the data the color and size picker needs. Exact stock is only shared when it is low.
     *
     * @param  Collection<int, ProductColor>  $colors
     * @param  Collection<int, Size>  $sizes
     * @return array<string, mixed>
     */
    private function pageData(Product $product, Collection $colors, Collection $sizes, ProductColor $selectedColor, ?string $selectedSizeSlug, int $lowStockThreshold): array
    {
        $locale = app()->getLocale();

        return [
            'productId' => $product->id,
            'maxQuantity' => (int) $this->settings->get('max_quantity_per_item'),
            'productName' => $product->translated('name'),
            'url' => route('products.show', $product),
            'color' => $selectedColor->color->slug,
            'size' => $selectedSizeSlug,
            'sizes' => $sizes->map(fn (Size $size): array => ['slug' => $size->slug, 'name' => $size->translated('name')])->values(),
            'colors' => $colors->mapWithKeys(fn (ProductColor $productColor): array => [
                $productColor->color->slug => [
                    'name' => $productColor->displayName(),
                    'images' => $productColor->images->map(fn (ProductImage $image): array => [
                        'src' => $image->url(1200),
                        'srcset' => $image->srcset(),
                    ])->values(),
                    'sizes' => $productColor->offeredVariants()->mapWithKeys(function (ProductVariant $variant) use ($product, $locale, $lowStockThreshold): array {
                        $price = $variant->price ?? $product->price;
                        $compareAtPrice = $variant->compare_at_price ?? $product->compare_at_price;

                        return [$variant->size->slug => [
                            'variantId' => $variant->id,
                            'available' => $variant->stock > 0,
                            'lowStock' => $variant->stock > 0 && $variant->stock <= $lowStockThreshold ? $variant->stock : null,
                            'price' => Money::format($price, $locale),
                            'compareAt' => $compareAtPrice !== null && $compareAtPrice > $price ? Money::format($compareAtPrice, $locale) : null,
                        ]];
                    }),
                ],
            ]),
        ];
    }

    /**
     * Build the breadcrumb trail: home, the category (and its parent), then the product.
     *
     * @return list<array{string, string|null}>
     */
    private function breadcrumbs(Product $product): array
    {
        $breadcrumbs = [[__('Home'), route('home')]];
        $category = $product->category;

        if ($category->parent?->is_active) {
            $breadcrumbs[] = [$category->parent->translated('name'), route('category.show', $category->parent)];
        }

        if ($category->is_active) {
            $breadcrumbs[] = [$category->translated('name'), route('category.show', $category)];
        }

        $breadcrumbs[] = [$product->translated('name'), null];

        return $breadcrumbs;
    }
}
