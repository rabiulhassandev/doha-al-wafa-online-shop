<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Services\ProductCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    /**
     * Show the storefront home page.
     */
    public function __invoke(ProductCatalog $catalog): View
    {
        return view('storefront.home', [
            'banners' => Banner::query()->live()->ordered()->get(),
            'categories' => Category::query()->active()->topLevel()->ordered()->get(),
            'newArrivals' => $catalog->listed()->orderByDesc('in_stock')->orderByDesc('published_at')->orderByDesc('id')->limit(8)->get(),
            'featured' => $catalog->listed()->where('is_featured', true)->orderByDesc('in_stock')->orderByDesc('published_at')->limit(8)->get(),
            'collections' => Collection::query()->live()->whereNotNull('banner_path')->ordered()->limit(3)->get(),
            'colors' => Color::query()
                ->whereHas('productColors', fn (Builder $query) => $query
                    ->listed()
                    ->whereHas('product', fn (Builder $query) => $query->listed()))
                ->ordered()
                ->limit(14)
                ->get(),
        ]);
    }
}
