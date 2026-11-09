<?php

it('shows the not found page in the language of the address', function (string $url, string $text, string $direction) {
    $this->get($url)
        ->assertNotFound()
        ->assertSee($text)
        ->assertSee("dir=\"{$direction}\"", false);
})->with([
    'unknown Arabic page' => ['/ar/no-such-page', 'لم نجد هذه الصفحة', 'rtl'],
    'missing Arabic product' => ['/ar/products/no-such-product', 'لم نجد هذه الصفحة', 'rtl'],
    'unknown English page' => ['/en/no-such-page', 'We couldn’t find that page', 'ltr'],
    'address without a language' => ['/no-such-page', 'We couldn’t find that page', 'ltr'],
]);
