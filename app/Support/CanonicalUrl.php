<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CanonicalUrl
{
    public static function forPath(string $path): string
    {
        $origin = rtrim(config('app.url'), '/');

        if (app()->isProduction()) {
            $origin = Str::replaceStart('http://', 'https://', $origin);
        }

        return $origin.$path;
    }
}
