<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CollectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Services\ProductCatalog;
use App\Support\ProductFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * List the products in a collection that is live right now.
     */
    public function show(Request $request, Collection $collection, ProductCatalog $catalog): View
    {
        abort_unless($collection->status() === CollectionStatus::Live, 404);

        $filters = ProductFilters::fromRequest($request);
        $products = $catalog->listed()->whereHas('collections', fn (Builder $query) => $query->whereKey($collection->id));

        return view('storefront.products.index', [
            'heading' => $collection->translated('name'),
            'description' => $collection->translated('description'),
            'bannerUrl' => $collection->imageUrl('banner_path'),
            'breadcrumbs' => [[__('Home'), route('home')], [$collection->translated('name'), null]],
            'pageUrl' => route('collection.show', $collection),
            'products' => $catalog->filter(clone $products, $filters)->paginate(24)->withQueryString(),
            'facets' => $catalog->facets($products),
            'filters' => $filters,
        ]);
    }
}
