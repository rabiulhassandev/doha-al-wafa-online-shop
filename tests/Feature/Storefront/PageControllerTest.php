<?php

use App\Models\Faq;
use App\Models\Page;

it('shows a page in the shopper’s language, with formatting', function () {
    Page::factory()->create([
        'slug' => 'exchange-and-returns',
        'title_ar' => 'الاستبدال والإرجاع',
        'body_ar' => "## الاستبدال\n\nخلال **7 أيام**.",
    ]);

    $this->get(route('pages.show', ['locale' => 'ar', 'page' => 'exchange-and-returns']))
        ->assertOk()
        ->assertSee('الاستبدال والإرجاع')
        ->assertSee('<h2>الاستبدال</h2>', false)
        ->assertSee('<strong>7 أيام</strong>', false);
});

it('removes code and unsafe links written into a page', function () {
    Page::factory()->create([
        'slug' => 'about',
        'body_en' => "Hello <script>alert('x')</script>\n\n[Click](javascript:alert(1))",
    ]);

    $this->get(route('pages.show', ['locale' => 'en', 'page' => 'about']))
        ->assertOk()
        ->assertDontSee('<script>', false)
        ->assertDontSee('javascript:', false);
});

it('hides draft pages', function () {
    Page::factory()->draft()->create(['slug' => 'terms']);

    $this->get(route('pages.show', ['locale' => 'en', 'page' => 'terms']))->assertNotFound();
});

it('links shown pages from the footer', function () {
    Page::factory()->create(['slug' => 'privacy', 'title_en' => 'Privacy', 'sort_order' => 20]);
    Page::factory()->create(['slug' => 'about', 'title_en' => 'About us', 'sort_order' => 10]);
    Page::factory()->draft()->create(['slug' => 'terms', 'title_en' => 'Terms']);

    $this->get(route('home', ['locale' => 'en']))
        ->assertSeeInOrder(['Help', 'About us', 'Privacy'])
        ->assertDontSee(route('pages.show', ['locale' => 'en', 'page' => 'terms']));
});

it('lists the shown questions in order', function () {
    Faq::factory()->create(['question_en' => 'Second question?', 'sort_order' => 20]);
    Faq::factory()->create(['question_en' => 'First question?', 'sort_order' => 10]);
    Faq::factory()->hidden()->create(['question_en' => 'Hidden question?']);

    $this->get(route('pages.faq', ['locale' => 'en']))
        ->assertOk()
        ->assertSeeInOrder(['First question?', 'Second question?'])
        ->assertDontSee('Hidden question?');
});
