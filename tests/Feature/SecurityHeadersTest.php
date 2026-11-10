<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

function assertSafetyHeaders(TestResponse $response): void
{
    $response
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'")
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(), usb=()');
}

it('sends the safety headers with store pages, admin pages and errors', function (string $url) {
    assertSafetyHeaders($this->get($url));
})->with([
    'store page' => '/en',
    'admin sign-in' => '/admin/login',
    'page not found' => '/en/no-such-page',
    'feed' => '/feeds/meta.csv',
]);

it('keeps browsers on HTTPS once the live store is served over it', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get('https://localhost/en')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('does not pin browsers to HTTPS outside the live store', function () {
    $this->get('https://localhost/en')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('/en')->assertHeaderMissing('Strict-Transport-Security');
});

it('lets a response set its own framing rules', function () {
    Route::get('test/embeddable', fn () => response('ok')->header('X-Frame-Options', 'DENY'));

    $this->get('/test/embeddable')->assertHeader('X-Frame-Options', 'DENY');
});
