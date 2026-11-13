<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ProductFeed;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Catalog feeds for Meta Commerce Manager and TikTok Catalog: "/feeds/meta.csv" and "/feeds/tiktok.csv".
 * "?locale=ar" gives the Arabic feed. When the owner sets a feed token, "?token=…" is needed.
 */
class ProductFeedController extends Controller
{
    private const CACHE_MINUTES = 30;

    public function __invoke(Request $request, string $platform, ProductFeed $productFeed, Settings $settings): Response
    {
        abort_unless(in_array($platform, ProductFeed::PLATFORMS, true), 404);

        $token = $settings->feedToken();
        $providedToken = $request->query('token');
        abort_if($token !== null && (! is_string($providedToken) || ! hash_equals($token, $providedToken)), 404);

        $locale = $request->query('locale');
        $locale = is_string($locale) && array_key_exists($locale, config('shop.locales')) ? $locale : config('shop.default_locale');

        // The text is cached rather than the products: the cache does not store models.
        $csv = Cache::remember(
            "product-feed.v2.{$platform}.{$locale}",
            now()->addMinutes(self::CACHE_MINUTES),
            fn (): string => $productFeed->csv($platform, $locale),
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
