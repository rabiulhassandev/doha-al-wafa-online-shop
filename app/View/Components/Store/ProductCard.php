<?php

namespace App\View\Components\Store;

use App\Models\Product;
use App\Models\ProductColor;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProductCard extends Component
{
    private const VISIBLE_SWATCHES = 5;

    public string $name;

    public string $url;

    /**
     * The colors a shopper can tap, each with its own photo and link.
     *
     * @var list<array{slug: string, name: string, hex: string, swatchUrl: ?string, image: array{src: string, srcset: string}, url: string, inStock: bool}>
     */
    public array $swatches;

    public int $hiddenSwatchCount;

    /** @var array{src: string, srcset: string}|null */
    public ?array $image;

    public string $price;

    public ?string $compareAtPrice;

    public bool $isPriceRange;

    public ?int $discountPercent;

    public bool $inStock;

    /**
     * Create a new component instance.
     *
     * @param  list<int>  $preferredColorIds  palette colors the shopper filtered by; their photo is shown first
     */
    public function __construct(public Product $product, array $preferredColorIds = [], public bool $eager = false)
    {
        $locale = app()->getLocale();
        $displayColor = $product->displayColor($preferredColorIds);
        $listedColors = $product->listedColors();

        $this->name = $product->translated('name');
        $this->url = $this->colorUrl($displayColor);
        $this->swatches = $listedColors
            ->sortBy(fn (ProductColor $productColor): int => $productColor->is($displayColor) ? 0 : 1)
            ->take(self::VISIBLE_SWATCHES)
            ->map(fn (ProductColor $productColor): array => [
                'slug' => $productColor->color->slug,
                'name' => $productColor->displayName($locale),
                'hex' => $productColor->color->hex,
                'swatchUrl' => $productColor->color->imageUrl('swatch_path'),
                'image' => $this->imageFor($productColor),
                'url' => $this->colorUrl($productColor),
                'inStock' => $productColor->isInStock(),
            ])
            ->values()
            ->all();
        $this->hiddenSwatchCount = max(0, $listedColors->count() - self::VISIBLE_SWATCHES);
        $this->image = $displayColor ? $this->imageFor($displayColor) : null;

        [$lowestPrice, $highestPrice] = $product->priceRange();
        $this->price = Money::format($lowestPrice, $locale);
        $this->isPriceRange = $lowestPrice !== $highestPrice;
        $this->compareAtPrice = $product->compare_at_price !== null && $product->compare_at_price > $lowestPrice
            ? Money::format($product->compare_at_price, $locale)
            : null;
        $this->discountPercent = $this->compareAtPrice !== null
            ? (int) round(100 - ($lowestPrice / $product->compare_at_price * 100))
            : null;
        $this->inStock = $product->isInStock();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.store.product-card');
    }

    /**
     * @return array{src: string, srcset: string}
     */
    private function imageFor(ProductColor $productColor): array
    {
        $image = $productColor->images->first();

        return ['src' => $image->url(800), 'srcset' => $image->srcset()];
    }

    private function colorUrl(?ProductColor $productColor): string
    {
        return route('products.show', array_filter([
            'product' => $this->product,
            'color' => $productColor?->color->slug,
        ]));
    }
}
