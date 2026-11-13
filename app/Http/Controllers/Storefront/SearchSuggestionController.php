<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ProductCatalog;
use App\Support\Money;
use App\Support\SearchText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    private const MIN_LENGTH = 2;

    /**
     * Suggest products, categories and collections while the shopper types.
     */
    public function __invoke(Request $request, ProductCatalog $catalog): JsonResponse
    {
        $term = SearchText::term($request->query('q'));

        if (mb_strlen(SearchText::normalize($term)) < self::MIN_LENGTH) {
            return response()->json(['products' => [], 'categories' => [], 'collections' => []]);
        }

        $products = $catalog->search($catalog->listed(), $term)
            ->orderByDesc('in_stock')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $categories = Category::query()
            ->visible()
            ->matchingName($term)
            ->ordered()
            ->limit(3)
            ->get();

        $collections = Collection::query()
            ->live()
            ->matchingName($term)
            ->ordered()
            ->limit(3)
            ->get();

        return response()->json([
            'products' => $products->map(function (Product $product): array {
                $color = $product->displayColor();

                return [
                    'name' => $product->translated('name'),
                    'url' => route('products.show', ['product' => $product, 'color' => $color?->color?->slug]),
                    'image' => $color?->images->first()?->url(400),
                    'price' => Money::format($product->priceRange()[0], app()->getLocale()),
                ];
            })->values(),
            'categories' => $categories->map(fn (Category $category): array => [
                'name' => $category->translated('name'),
                'url' => route('category.show', $category),
            ])->values(),
            'collections' => $collections->map(fn (Collection $collection): array => [
                'name' => $collection->translated('name'),
                'url' => route('collection.show', $collection),
            ])->values(),
        ]);
    }
}
