<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser safety headers on every response.
 *
 * There is no script allow-list (Content-Security-Policy "script-src"): Alpine needs "unsafe-eval" and the
 * ad pixels load from several hosts, so such a list would protect little. Framing is limited instead.
 */
class SecurityHeaders
{
    /**
     * Headers sent with every response.
     */
    private const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        // Only this site may show its pages in a frame (stops click-jacking of checkout and admin).
        'X-Frame-Options' => 'SAMEORIGIN',
        'Content-Security-Policy' => "frame-ancestors 'self'",
        // Checkout's map pin uses the location; nothing uses the camera, microphone or payment sheet yet.
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(self), payment=(), usb=()',
    ];

    /**
     * How long browsers keep to HTTPS once they have seen it: one year.
     */
    private const HSTS_MAX_AGE = 31536000;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age='.self::HSTS_MAX_AGE.'; includeSubDomains');
        }

        return $response;
    }
}
