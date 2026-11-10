<?php

namespace App\Http\Middleware;

use App\Support\Attribution;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers where a visit came from (campaign tags, ad click IDs, short links, other sites) so the
 * order placed later can say which campaign brought it. Direct visits keep the earlier source.
 */
class CaptureAttribution
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ($attribution = Attribution::fromRequest($request)) !== null) {
            Cookie::queue(Attribution::COOKIE, $attribution->toCookieValue(), Attribution::COOKIE_MINUTES);
        }

        return $next($request);
    }
}
