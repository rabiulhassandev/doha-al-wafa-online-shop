<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ProductCatalog;
use App\Support\ProductFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List the products in a category and its sub-categories.
     */
    public function show(Request $request, Category $category, ProductCatalog $catalog): View
    {
        abort_unless($category->is_active, 404);

        $category->load(['parent', 'children' => fn ($query) => $query->active()->ordered()]);
        abort_if($category->parent !== null && ! $category->parent->is_active, 404);

        $filters = ProductFilters::fromRequest($request);
        $products = $catalog->listed()->whereIn('category_id', [$category->id, ...$category->children->modelKeys()]);

        $breadcrumbs = [[__('Home'), route('home')]];

        if ($category->parent) {
            $breadcrumbs[] = [$category->parent->translated('name'), route('category.show', $category->parent)];
        }

        $breadcrumbs[] = [$category->translated('name'), null];

        return view('storefront.products.index', [
            'heading' => $category->translated('name'),
            'description' => $category->translated('description'),
            'breadcrumbs' => $breadcrumbs,
            'subcategories' => $category->children,
            'pageUrl' => route('category.show', $category),
            'products' => $catalog->filter(clone $products, $filters)->paginate(24)->withQueryString(),
            'facets' => $catalog->facets($products),
            'filters' => $filters,
        ]);
    }
}
