<?php

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Services\Settings;
use Illuminate\Testing\TestResponse;

/**
 * Get the schema.org blocks a page gives search engines, keyed by type.
 *
 * @return array<string, array<string, mixed>>
 */
function structuredData(TestResponse $response): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);

    return collect($matches[1])
        ->map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR))
        ->keyBy('@type')
        ->all();
}

describe('robots.txt', function () {
    it('keeps private pages out and points to the sitemap on the live store', function () {
        app()->detectEnvironment(fn (): string => 'production');

        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /*/checkout')
            ->assertSee('Sitemap: '.route('sitemap'))
            ->assertDontSee("Disallow: /\n", false);
    });

    it('keeps search engines out of every other copy of the store', function () {
        $this->get('/robots.txt')->assertOk()->assertSee("User-agent: *\nDisallow: /\n", false);
    });
});

describe('sitemap', function () {
    it('lists the pages shoppers can see, in each language, with their alternates', function () {
        $product = listedProduct(['slug' => 'classic-jelabiya']);
        Product::factory()->create(['slug' => 'draft-jelabiya']);
        Category::factory()->create(['slug' => 'hidden', 'is_active' => false]);
        Page::factory()->create(['slug' => 'about', 'is_published' => true]);
        Page::factory()->create(['slug' => 'draft-page', 'is_published' => false]);

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $locations = collect(iterator_to_array($xml->url, false))->map(fn (SimpleXMLElement $url): string => (string) $url->loc);

        expect($locations)->toContain(
            route('home', ['locale' => 'en']),
            route('home', ['locale' => 'ar']),
            route('products.show', ['locale' => 'en', 'product' => $product]),
            route('products.show', ['locale' => 'ar', 'product' => $product]),
            route('category.show', ['locale' => 'ar', 'category' => $product->category]),
            route('pages.show', ['locale' => 'en', 'page' => 'about']),
            route('pages.faq', ['locale' => 'ar']),
        );
        expect($locations->implode(' '))->not->toContain('draft-jelabiya')->not->toContain('hidden')->not->toContain('draft-page');

        $productEntry = collect(iterator_to_array($xml->url, false))->first(fn (SimpleXMLElement $url): bool => (string) $url->loc === route('products.show', ['locale' => 'ar', 'product' => $product]));
        $alternates = collect(iterator_to_array($productEntry->children('http://www.w3.org/1999/xhtml')->link, false))
            ->mapWithKeys(fn (SimpleXMLElement $link): array => [(string) $link->attributes()->hreflang => (string) $link->attributes()->href]);

        expect($alternates->all())->toBe([
            'en' => route('products.show', ['locale' => 'en', 'product' => $product]),
            'ar' => route('products.show', ['locale' => 'ar', 'product' => $product]),
            'x-default' => route('products.show', ['locale' => 'en', 'product' => $product]),
        ]);
        expect((string) $productEntry->lastmod)->toBe($product->fresh()->updated_at->toAtomString());
    });
});

describe('structured data', function () {
    it('describes a product with each color and size, its price and stock', function () {
        $product = listedProduct(['name_en' => 'Classic crepe jelabiya', 'code' => 'JLB012', 'price' => 18500], ['navy' => ['56' => 3, '58' => 0]]);
        $soldOut = $product->variants()->whereHas('size', fn ($query) => $query->where('slug', '58'))->sole();
        $soldOut->update(['price' => 19500]);

        $data = structuredData($this->get(route('products.show', ['locale' => 'en', 'product' => $product])));
        $group = $data['ProductGroup'];

        expect($group)->toMatchArray([
            'name' => 'Classic crepe jelabiya',
            'productGroupID' => 'JLB012',
            'url' => route('products.show', ['locale' => 'en', 'product' => $product]),
            'brand' => ['@type' => 'Brand', 'name' => 'Doha AL WAFA'],
        ]);
        expect($group['hasVariant'])->toHaveCount(2);

        $variants = collect($group['hasVariant'])->keyBy('size');
        expect($variants['56'])->toMatchArray(['name' => 'Classic crepe jelabiya – Navy / 56', 'color' => 'Navy']);
        expect($variants['56']['offers'])->toMatchArray([
            'price' => '185.00',
            'priceCurrency' => 'QAR',
            'availability' => 'https://schema.org/InStock',
            'url' => route('products.show', ['locale' => 'en', 'product' => $product, 'color' => 'navy', 'size' => '56']),
        ]);
        expect($variants['58']['offers'])->toMatchArray(['price' => '195.00', 'availability' => 'https://schema.org/OutOfStock']);
        expect($variants['58']['sku'])->toBe($soldOut->sku);
    });

    it('describes the breadcrumb trail', function () {
        $product = listedProduct(['name_en' => 'Classic crepe jelabiya']);

        $trail = structuredData($this->get(route('products.show', ['locale' => 'en', 'product' => $product])))['BreadcrumbList']['itemListElement'];

        expect($trail[0])->toBe(['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home', ['locale' => 'en'])]);
        expect(end($trail))->toMatchArray(['name' => 'Classic crepe jelabiya', 'item' => route('products.show', ['locale' => 'en', 'product' => $product])]);
    });

    it('describes the store and its search on the home page', function () {
        app(Settings::class)->set(['contact_phone' => '+97444441234', 'instagram_url' => 'https://www.instagram.com/dohaalwafa']);

        $data = structuredData($this->get(route('home', ['locale' => 'ar'])));

        expect($data['OnlineStore'])->toMatchArray([
            'name' => 'Doha AL WAFA',
            'url' => route('home', ['locale' => 'ar']),
            'telephone' => '+97444441234',
            'sameAs' => ['https://www.instagram.com/dohaalwafa'],
        ]);
        expect($data['WebSite']['potentialAction']['target']['urlTemplate'])->toBe(route('search', ['locale' => 'ar']).'?q={search_term_string}');
    });

    it('cannot be broken out of by product text', function () {
        $product = listedProduct(['name_en' => 'Jelabiya </script><script>alert(1)</script>']);

        $response = $this->get(route('products.show', ['locale' => 'en', 'product' => $product]));

        expect($response->getContent())->not->toContain('<script>alert(1)</script>');
        expect(structuredData($response)['ProductGroup']['name'])->toBe('Jelabiya </script><script>alert(1)</script>');
    });
});

describe('page addresses', function () {
    it('names the plain address as the one to index, with the other language and a default', function () {
        $product = listedProduct();

        $this->get(route('products.show', ['locale' => 'ar', 'product' => $product, 'color' => 'navy', 'utm_source' => 'tiktok']))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('products.show', ['locale' => 'ar', 'product' => $product]).'">', false)
            ->assertSee('hreflang="x-default"', false);

        $this->get(route('shop', ['locale' => 'en', 'sort' => 'newest', 'page' => 2]))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('shop', ['locale' => 'en']).'">', false);
    });

    it('keeps search results out of search engines', function () {
        $this->get(route('search', ['locale' => 'en', 'q' => 'navy']))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->get(route('shop', ['locale' => 'en']))->assertDontSee('noindex', false);
    });
});
