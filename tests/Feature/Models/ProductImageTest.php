<?php

use App\Models\ProductImage;

it('picks the smallest stored width that is wide enough', function (int $requested, string $expectedFile) {
    $image = ProductImage::factory()->make(['directory' => 'products/1/abc', 'widths' => [1200, 800, 400]]);

    expect($image->url($requested))->toEndWith("/storage/products/1/abc/{$expectedFile}");
})->with([
    'small' => [300, '400.webp'],
    'exact' => [800, '800.webp'],
    'between' => [900, '1200.webp'],
    'larger than stored' => [2000, '1200.webp'],
]);

it('lists every stored width for responsive images', function () {
    $image = ProductImage::factory()->make(['directory' => 'products/1/abc', 'widths' => [800, 400]]);

    expect($image->srcset())
        ->toContain('/storage/products/1/abc/400.webp 400w')
        ->toContain('/storage/products/1/abc/800.webp 800w');
});
