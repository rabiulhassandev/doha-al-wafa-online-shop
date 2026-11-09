<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ProductCatalog;
use App\Support\ProductFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    /**
     * Show the products matching the shopper's search.
     */
    public function __invoke(Request $request, ProductCatalog $catalog): View
    {
        $term = Str::limit(trim((string) $request->query('q')), 100, '');
        $filters = ProductFilters::fromRequest($request);
        $products = $catalog->search($catalog->listed(), $term);

        return view('storefront.products.index', [
            'heading' => $term === '' ? __('Search') : __('Results for “:term”', ['term' => $term]),
            'description' => null,
            'searchTerm' => $term,
            'breadcrumbs' => [[__('Home'), route('home')], [__('Search'), null]],
            'pageUrl' => route('search'),
            'products' => $catalog->filter(clone $products, $filters)->paginate(24)->withQueryString(),
            'facets' => $catalog->facets($products),
            'filters' => $filters,
        ]);
    }
}
