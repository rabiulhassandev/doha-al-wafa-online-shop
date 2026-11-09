<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Models\Collection;
use Illuminate\View\View;

/**
 * Shares the store menu (categories and live collections) with the header, menu and footer.
 *
 * Loaded once per request. It is not stored in the cache: the cache refuses to unserialize
 * models (config "cache.serializable_classes"), and the two small queries are cheap.
 */
class StoreNavigationComposer
{
    public function compose(View $view): void
    {
        $view->with('navigation', once(fn (): array => [
            'categories' => Category::query()
                ->active()
                ->topLevel()
                ->ordered()
                ->with(['children' => fn ($query) => $query->active()->ordered()])
                ->get(),
            'collections' => Collection::query()->live()->ordered()->get(),
        ]));
    }
}
