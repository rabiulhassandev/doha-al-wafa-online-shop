<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Settings;

/**
 * Read a CSV feed into rows keyed by column name.
 *
 * @return list<array<string, string>>
 */
function feedRows(string $csv): array
{
    $handle = fopen('php://memory', 'r+');
    fwrite($handle, $csv);
    rewind($handle);

    $header = fgetcsv($handle, escape: '');
    $rows = [];

    while (($row = fgetcsv($handle, escape: '')) !== false) {
        $rows[] = array_combine($header, $row);
    }

    fclose($handle);

    return $rows;
}

it('lists every color and size as its own item, grouped by product', function () {
    $product = listedProduct(
        ['name_en' => 'Classic crepe jelabiya', 'code' => 'JLB012', 'price' => 18500, 'description_en' => '<p>Soft <strong>crepe</strong>.</p>'],
        ['navy' => ['56' => 3, '58' => 0], 'olive' => ['56' => 2]],
    );

    $response = $this->get('/feeds/meta.csv')->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('text/csv; charset=UTF-8');
    $rows = collect(feedRows($response->getContent()))->keyBy(fn (array $row): string => $row['color'].' '.$row['size']);
    expect($rows->keys()->sort()->values()->all())->toBe(['Navy 56', 'Navy 58', 'Olive 56']);

    $navy56 = $rows['Navy 56'];
    $variant = ProductVariant::query()->whereHas('productColor.color', fn ($query) => $query->where('slug', 'navy'))->whereHas('size', fn ($query) => $query->where('slug', '56'))->sole();
    expect($navy56)->toMatchArray([
        'id' => $variant->sku,
        'item_group_id' => 'JLB012',
        'title' => 'Classic crepe jelabiya',
        'description' => 'Soft crepe.',
        'availability' => 'in stock',
        'condition' => 'new',
        'price' => '185.00 QAR',
        'sale_price' => '',
        'brand' => 'BANAT AL WAFA',
        'google_product_category' => 'Apparel & Accessories > Clothing',
    ]);
    expect($navy56['link'])->toBe(route('products.show', ['locale' => 'en', 'product' => $product, 'color' => 'navy', 'size' => '56', 'utm_source' => 'meta', 'utm_medium' => 'catalog']));
    expect($navy56['image_link'])->toBe($variant->productColor->images->first()->url(1200));
    expect($rows['Navy 58']['availability'])->toBe('out of stock');
});

it('shows a sale with the old price as the price', function () {
    listedProduct(['price' => 15000, 'compare_at_price' => 20000]);

    $row = feedRows($this->get('/feeds/meta.csv')->getContent())[0];

    expect([$row['price'], $row['sale_price']])->toBe(['200.00 QAR', '150.00 QAR']);
});

it('lists an item sold by size with an empty color and a size-only link', function () {
    $product = listedProduct();
    $product->colors()->sole()->update(['color_id' => null]);

    [$row] = feedRows($this->get('/feeds/meta.csv')->assertOk()->getContent());

    expect($row['color'])->toBe('')
        ->and($row['size'])->toBe('56')
        ->and($row['link'])->toBe(route('products.show', [
            'locale' => 'en', 'product' => $product, 'size' => '56', 'utm_source' => 'meta', 'utm_medium' => 'catalog',
        ]));
});

it('leaves out products shoppers cannot see and sizes no longer sold', function () {
    $listed = listedProduct(['name_en' => 'Listed'], ['navy' => ['56' => 3, '58' => 3]]);
    $listed->variants()->whereHas('size', fn ($query) => $query->where('slug', '58'))->update(['is_active' => false]);
    Product::factory()->create(['name_en' => 'Draft']);

    $rows = feedRows($this->get('/feeds/meta.csv')->getContent());

    expect($rows)->toHaveCount(1);
    expect($rows[0]['title'])->toBe('Listed');
});

it('names the item ID the way TikTok reads it', function () {
    listedProduct();

    $rows = feedRows($this->get('/feeds/tiktok.csv')->getContent());

    expect(array_key_first($rows[0]))->toBe('sku_id');
    expect($rows[0]['link'])->toContain('utm_source=tiktok');
});

it('gives the feed in Arabic', function () {
    $product = listedProduct(['name_ar' => 'جلابية كريب كلاسيك']);

    $row = feedRows($this->get('/feeds/meta.csv?locale=ar')->getContent())[0];

    expect($row['title'])->toBe('جلابية كريب كلاسيك');
    expect($row['link'])->toStartWith(route('products.show', ['locale' => 'ar', 'product' => $product]));
});

it('needs the feed password once the owner sets one', function () {
    listedProduct();
    app(Settings::class)->set(['feed_token' => 'secret-feed-123']);

    $this->get('/feeds/meta.csv')->assertNotFound();
    $this->get('/feeds/meta.csv?token=wrong-token')->assertNotFound();
    $this->get('/feeds/meta.csv?token=secret-feed-123')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
});

it('has feeds for Meta and TikTok only', function () {
    $this->get('/feeds/google.csv')->assertNotFound();
});
