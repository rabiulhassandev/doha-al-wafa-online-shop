<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;

class LocalizedUrl
{
    public function __construct(private readonly Request $request) {}

    /**
     * Get the URL of the current page in another locale, keeping its parameters and query string.
     *
     * Signed pages, such as an order's thank-you page, get a fresh signature for the new URL,
     * but only when the current page's signature is genuine.
     */
    public function current(string $locale): string
    {
        $route = $this->request->route();

        if ($route === null || $route->getName() === null || ! in_array('locale', $route->parameterNames(), true)) {
            return route('home', ['locale' => $locale]);
        }

        $parameters = [
            ...$route->parameters(),
            ...$this->request->query(),
            'locale' => $locale,
        ];

        if (! $this->request->query->has('signature')) {
            return route($route->getName(), $parameters);
        }

        if (! $this->request->hasValidSignature()) {
            return route('home', ['locale' => $locale]);
        }

        $parameters = Arr::except($parameters, ['signature', 'expires']);
        $expires = $this->request->query('expires');

        return $expires === null
            ? URL::signedRoute($route->getName(), $parameters)
            : URL::temporarySignedRoute($route->getName(), CarbonImmutable::createFromTimestamp((int) $expires), $parameters);
    }
}
