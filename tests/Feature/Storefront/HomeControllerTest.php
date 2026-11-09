<?php

use App\Services\Settings;

it('renders the English home page left to right', function () {
    $this->get(route('home', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr">', false)
        ->assertSee('Jelabiyas in the colors you love');
});

it('renders the Arabic home page right to left', function () {
    $this->get(route('home', ['locale' => 'ar']))
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', false)
        ->assertSee('جلابيات بألوانك المفضلة');
});

it('returns 404 for a language the store does not support', function () {
    $this->get('/fr')->assertNotFound();
});

it('remembers the language for the next visit', function () {
    $this->get(route('home', ['locale' => 'ar']))->assertCookie('locale', 'ar');
});

it('links to the same page in the other language', function () {
    $this->get(route('home', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('href="'.route('home', ['locale' => 'ar']).'"', false);
});

it('shows the announcement written for the current language', function () {
    app(Settings::class)->set([
        'announcement_en' => 'Free delivery this week',
        'announcement_ar' => 'توصيل مجاني هذا الأسبوع',
    ]);

    $this->get(route('home', ['locale' => 'ar']))
        ->assertOk()
        ->assertSee('توصيل مجاني هذا الأسبوع')
        ->assertDontSee('Free delivery this week');
});

it('escapes the announcement text', function () {
    app(Settings::class)->set(['announcement_en' => '<script>alert("x")</script>']);

    $this->get(route('home', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert("x")</script>', false);
});

it('shows the WhatsApp link in the footer once the number is saved', function () {
    app(Settings::class)->set(['whatsapp_number' => '+97455551234']);

    $this->get(route('home', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('href="https://wa.me/97455551234"', false)
        ->assertSee('+974 5555 1234');
});
