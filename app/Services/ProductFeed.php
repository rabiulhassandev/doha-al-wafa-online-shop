<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

/**
 * Catalog feeds for Meta and TikTok: one row per color and size, grouped by product, with the color's
 * own photo and a link that opens that color and size. The IDs are SKUs, the same IDs the pixels send.
 */
class ProductFeed
{
    public const PLATFORMS = ['meta', 'tiktok'];

    /**
     * Columns in order. TikTok calls the item ID "sku_id"; everything else matches.
     */
    private const COLUMNS = [
        'id', 'item_group_id', 'title', 'description', 'availability', 'condition', 'price', 'sale_price',
        'link', 'image_link', 'additional_image_link', 'brand', 'color', 'size', 'google_product_category', 'product_type',
    ];

    private const GOOGLE_PRODUCT_CATEGORY = 'Apparel & Accessories > Clothing';

    private const MAX_ADDITIONAL_IMAGES = 9;

    public function __construct(
        private readonly Settings $settings,
        private readonly CartService $cartService,
    ) {}

    /**
     * Build the feed as CSV, in the given language.
     */
    public function csv(string $platform, string $locale): string
    {
        $columns = $platform === 'tiktok' ? ['sku_id', ...array_slice(self::COLUMNS, 1)] : self::COLUMNS;
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $columns, escape: '');

        foreach ($this->rows($platform, $locale) as $row) {
            fputcsv($handle, $row, escape: '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @return iterable<list<string>>
     */
    private function rows(string $platform, string $locale): iterable
    {
        $products = Product::query()
            ->listed()
            ->with(['category', 'colors.color', 'colors.images', 'colors.variants.size'])
            ->orderBy('id')
            ->lazy(100);

        foreach ($products as $product) {
            foreach ($product->listedColors() as $productColor) {
                foreach ($productColor->variants->where('is_active', true) as $variant) {
                    $variant->setRelation('product', $product)->setRelation('productColor', $productColor);

                    yield $this->row($platform, $locale, $product, $productColor, $variant);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function row(string $platform, string $locale, Product $product, ProductColor $productColor, ProductVariant $variant): array
    {
        $price = $variant->effectivePrice();
        $compareAtPrice = $variant->compare_at_price ?? $product->compare_at_price;
        $isOnSale = $compareAtPrice !== null && $compareAtPrice > $price;
        $images = $productColor->images;
        $name = (string) $product->translated('name', $locale);

        return [
            $variant->sku,
            $product->code ?: 'P'.$product->id,
            $name,
            Str::limit(trim(strip_tags((string) $product->translated('description', $locale))) ?: $name, 4900, ''),
            $this->cartService->maxQuantity($variant) > 0 ? 'in stock' : 'out of stock',
            'new',
            self::price($isOnSale ? $compareAtPrice : $price),
            $isOnSale ? self::price($price) : '',
            route('products.show', [
                'locale' => $locale,
                'product' => $product,
                'color' => $productColor->color->slug,
                'size' => $variant->size->slug,
                'utm_source' => $platform,
                'utm_medium' => 'catalog',
            ]),
            $images->first()->url(1200),
            $images->skip(1)->take(self::MAX_ADDITIONAL_IMAGES)->map(fn (ProductImage $image): string => $image->url(1200))->implode(','),
            (string) $this->settings->get('store_name'),
            $productColor->displayName($locale),
            (string) $variant->size->translated('name', $locale),
            self::GOOGLE_PRODUCT_CATEGORY,
            (string) $product->category?->translated('name', $locale),
        ];
    }

    /**
     * Write a price the way the platforms read it, e.g. "185.00 QAR".
     */
    private static function price(int $dirhams): string
    {
        return number_format($dirhams / 100, 2, '.', '').' QAR';
    }
}
