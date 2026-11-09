<?php

namespace App\Support;

use Illuminate\Http\Request;

class LocalizedUrl
{
    public function __construct(private readonly Request $request) {}

    /**
     * Get the URL of the current page in another locale, keeping its parameters and query string.
     */
    public function current(string $locale): string
    {
        $route = $this->request->route();

        if ($route === null || $route->getName() === null || ! in_array('locale', $route->parameterNames(), true)) {
            return route('home', ['locale' => $locale]);
        }

        return route($route->getName(), [
            ...$route->parameters(),
            ...$this->request->query(),
            'locale' => $locale,
        ]);
    }
}
