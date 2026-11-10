<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Sitemap;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * The files search engines read first: "/robots.txt" and "/sitemap.xml".
 */
class SeoController extends Controller
{
    private const SITEMAP_CACHE_MINUTES = 60;

    /**
     * Tell search engines what to crawl. Anything but the live store is kept out of search results entirely.
     */
    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? [
                'User-agent: *',
                // Private or personal pages; their own tags say "noindex" too.
                'Disallow: /admin',
                'Disallow: /*/bag',
                'Disallow: /*/checkout',
                'Disallow: /*/account',
                'Disallow: /*/orders/',
                'Disallow: /*/wishlist',
                'Disallow: /*/search',
                '',
                'Sitemap: '.route('sitemap'),
            ]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * List every page shoppers can see, for search engines.
     */
    public function sitemap(Sitemap $sitemap): Response
    {
        // The text is cached rather than the models: the cache does not store models.
        $xml = Cache::remember('sitemap.xml', now()->addMinutes(self::SITEMAP_CACHE_MINUTES), fn (): string => $sitemap->xml());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
