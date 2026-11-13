<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Schema.org data (JSON-LD) that lets search engines show the store, its products and prices richly.
 * Built in the current language; amounts in QAR.
 */
final class StructuredData
{
    private const SOCIAL_SETTINGS = ['instagram_url', 'tiktok_url', 'facebook_url', 'snapchat_url'];

    /**
     * The store itself, with its contact details and social accounts.
     *
     * @return array<string, mixed>
     */
    public static function store(Settings $settings): array
    {
        $phone = $settings->phone('contact_phone') ?? $settings->phone('whatsapp_number');
        $socialProfiles = array_values(array_filter(array_map(fn (string $key): ?string => $settings->get($key) ?: null, self::SOCIAL_SETTINGS)));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'OnlineStore',
            'name' => (string) $settings->get('store_name'),
            'url' => route('home'),
            'telephone' => $phone?->e164(),
            'email' => $settings->get('contact_email') ?: null,
            'sameAs' => $socialProfiles === [] ? null : $socialProfiles,
            'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'QA'],
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * The website, with its product search.
     *
     * @return array<string, mixed>
     */
    public static function website(string $storeName): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $storeName,
            'url' => route('home'),
            'inLanguage' => app()->getLocale(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('search').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * The breadcrumb trail; the last step, the page itself, links to the current address.
     *
     * @param  list<array{0: string, 1: string|null}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item[0],
                'item' => $item[1] ?? url()->current(),
            ], $items, array_keys($items)),
        ];
    }

    /**
     * A product as a group of variants, one per color and size, each with its own photos, price and stock.
     * Expects the listed colors with their color, images and offered sizes loaded.
     *
     * @param  Collection<int, ProductColor>  $colors
     * @return array<string, mixed>
     */
    public static function product(Product $product, Collection $colors, string $storeName): array
    {
        $name = (string) $product->translated('name');
        $description = trim(strip_tags((string) $product->translated('description')));
        $variants = [];

        foreach ($colors as $productColor) {
            $colorName = $productColor->displayName();
            $images = $productColor->images->map(fn (ProductImage $image): string => $image->url(1200))->values()->all();

            foreach ($productColor->offeredVariants() as $variant) {
                $sizeName = (string) $variant->size->translated('name');

                $variants[] = [
                    '@type' => 'Product',
                    'sku' => $variant->sku,
                    'name' => $colorName === null ? "{$name} – {$sizeName}" : "{$name} – {$colorName} / {$sizeName}",
                    ...($colorName === null ? [] : ['color' => $colorName]),
                    'size' => $sizeName,
                    'image' => $images,
                    'offers' => self::offer($product, $productColor, $variant),
                ];
            }
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ProductGroup',
            'name' => $name,
            'description' => $description === '' ? null : Str::limit($description, 5000, ''),
            'url' => route('products.show', $product),
            'brand' => ['@type' => 'Brand', 'name' => $storeName],
            'productGroupID' => $product->code ?: 'P'.$product->id,
            'variesBy' => $colors->contains(fn (ProductColor $color): bool => $color->hasColor())
                ? ['https://schema.org/color', 'https://schema.org/size']
                : ['https://schema.org/size'],
            'hasVariant' => $variants,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, string>
     */
    private static function offer(Product $product, ProductColor $productColor, ProductVariant $variant): array
    {
        return [
            '@type' => 'Offer',
            'url' => route('products.show', ['product' => $product, 'color' => $productColor->color?->slug, 'size' => $variant->size->slug]),
            'price' => number_format(($variant->price ?? $product->price) / 100, 2, '.', ''),
            'priceCurrency' => 'QAR',
            'availability' => $variant->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ];
    }
}
