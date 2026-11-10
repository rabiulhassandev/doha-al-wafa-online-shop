<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use Carbon\CarbonInterface;
use XMLWriter;

/**
 * The sitemap for search engines: every page shoppers can see, in each language, with links to the
 * same page in the other languages so the right one shows in Arabic and English searches.
 */
class Sitemap
{
    /**
     * Build the sitemap as XML.
     */
    public function xml(): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

        foreach ($this->pages() as [$routeName, $parameters, $lastModified]) {
            $alternates = $this->alternates($routeName, $parameters);

            foreach ($alternates as $url) {
                $xml->startElement('url');
                $xml->writeElement('loc', $url);

                if ($lastModified !== null) {
                    $xml->writeElement('lastmod', $lastModified->toAtomString());
                }

                foreach ([...$alternates, 'x-default' => $alternates[config('shop.default_locale')]] as $locale => $alternateUrl) {
                    $xml->startElement('xhtml:link');
                    $xml->writeAttribute('rel', 'alternate');
                    $xml->writeAttribute('hreflang', $locale);
                    $xml->writeAttribute('href', $alternateUrl);
                    $xml->endElement();
                }

                $xml->endElement();
            }
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Every page to list, as [route name, route parameters, last changed].
     *
     * @return iterable<array{0: string, 1: array<string, mixed>, 2: CarbonInterface|null}>
     */
    private function pages(): iterable
    {
        yield ['home', [], null];
        yield ['shop', [], null];

        foreach (Category::query()->active()->ordered()->get(['id', 'slug', 'updated_at']) as $category) {
            yield ['category.show', ['category' => $category], $category->updated_at];
        }

        foreach (Collection::query()->live()->ordered()->get(['id', 'slug', 'updated_at']) as $collection) {
            yield ['collection.show', ['collection' => $collection], $collection->updated_at];
        }

        foreach (Product::query()->listed()->orderBy('id')->lazy(200) as $product) {
            yield ['products.show', ['product' => $product], $product->updated_at];
        }

        foreach (Page::query()->published()->ordered()->get(['id', 'slug', 'updated_at']) as $page) {
            yield ['pages.show', ['page' => $page], $page->updated_at];
        }

        yield ['pages.faq', [], null];
        yield ['contact.show', [], null];
    }

    /**
     * Get a page's address in each language.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, string>
     */
    private function alternates(string $routeName, array $parameters): array
    {
        $urls = [];

        foreach (array_keys(config('shop.locales')) as $locale) {
            $urls[$locale] = route($routeName, [...$parameters, 'locale' => $locale]);
        }

        return $urls;
    }
}
