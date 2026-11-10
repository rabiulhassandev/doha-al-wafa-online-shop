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

        $token = (string) $settings->get('feed_token');
        abort_if($token !== '' && ! hash_equals($token, (string) $request->query('token')), 404);

        $locale = array_key_exists((string) $request->query('locale'), config('shop.locales'))
            ? (string) $request->query('locale')
            : config('shop.default_locale');

        // The text is cached rather than the products: the cache does not store models.
        $csv = Cache::remember(
            "product-feed.{$platform}.{$locale}",
            now()->addMinutes(self::CACHE_MINUTES),
            fn (): string => $productFeed->csv($platform, $locale),
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }
}
