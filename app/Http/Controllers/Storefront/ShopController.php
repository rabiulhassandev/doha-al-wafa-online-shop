<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ProductCatalog;
use App\Support\ProductFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * List every product.
     */
    public function __invoke(Request $request, ProductCatalog $catalog): View
    {
        $filters = ProductFilters::fromRequest($request);
        $products = $catalog->listed();

        return view('storefront.products.index', [
            'heading' => __('Shop all'),
            'description' => null,
            'breadcrumbs' => [[__('Home'), route('home')], [__('Shop all'), null]],
            'pageUrl' => route('shop'),
            'products' => $catalog->filter(clone $products, $filters)->paginate(24)->withQueryString(),
            'facets' => $catalog->facets($products),
            'filters' => $filters,
        ]);
    }
}
