<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCatalog;
use App\Support\Money;
use App\Support\SearchText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchSuggestionController extends Controller
{
    private const MIN_LENGTH = 2;

    /**
     * Suggest products and categories while the shopper types.
     */
    public function __invoke(Request $request, ProductCatalog $catalog): JsonResponse
    {
        $term = Str::limit(trim((string) $request->query('q')), 100, '');

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return response()->json(['products' => [], 'categories' => []]);
        }

        $products = $catalog->search($catalog->listed(), $term)
            ->orderByDesc('in_stock')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        $like = '%'.addcslashes(SearchText::normalize($term), '%_\\').'%';
        $categories = Category::query()
            ->active()
            ->where(fn (Builder $query) => $query->where('name_en', 'like', $like)->orWhere('name_ar', 'like', $like))
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
        ]);
    }
}
