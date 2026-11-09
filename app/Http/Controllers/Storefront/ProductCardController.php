<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ProductCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductCardController extends Controller
{
    private const MAX_IDS = 24;

    /**
     * Render product cards for IDs kept in the shopper's browser, such as the wishlist.
     */
    public function __invoke(Request $request, ProductCatalog $catalog): View
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter(fn (string $id): bool => ctype_digit($id))
            ->map(fn (string $id): int => (int) $id)
            ->unique()
            ->take(self::MAX_IDS)
            ->values()
            ->all();

        return view('storefront.products.cards', [
            'products' => $ids === [] ? collect() : $catalog->byIds($ids),
        ]);
    }
}
