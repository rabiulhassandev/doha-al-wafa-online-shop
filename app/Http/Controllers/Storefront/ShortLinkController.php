<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Opens a short link ("/p/k7q2mx") at its store page, counting the visit.
 */
class ShortLinkController extends Controller
{
    /**
     * Apps that fetch a link only to draw its preview. Their visits are not counted.
     */
    private const PREVIEW_AGENTS = '/bot|crawl|spider|facebookexternalhit|facebot|whatsapp|telegram|slack|discord|twitter|linkedin|pinterest|embedly|preview/i';

    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $shortLink = ShortLink::query()->where('code', Str::lower($code))->where('is_active', true)->first();

        abort_if($shortLink === null, 404);

        if (preg_match(self::PREVIEW_AGENTS, (string) $request->userAgent()) !== 1) {
            $shortLink->increment('clicks');
        }

        return redirect()->away($shortLink->targetUrl());
    }
}
