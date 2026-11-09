<?php

it('redirects to the English home page by default', function () {
    $this->get('/')->assertRedirectToRoute('home', ['locale' => 'en']);
});

it('redirects to the Arabic home page when the browser prefers Arabic', function () {
    $this->get('/', ['Accept-Language' => 'ar-QA,ar;q=0.9,en;q=0.8'])
        ->assertRedirectToRoute('home', ['locale' => 'ar']);
});

it('prefers the saved language over the browser language', function () {
    $this->withCookie('locale', 'ar')
        ->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])
        ->assertRedirectToRoute('home', ['locale' => 'ar']);
});

it('falls back to the browser language when the saved language is not supported', function () {
    $this->withCookie('locale', 'fr')
        ->get('/', ['Accept-Language' => 'ar'])
        ->assertRedirectToRoute('home', ['locale' => 'ar']);
});
