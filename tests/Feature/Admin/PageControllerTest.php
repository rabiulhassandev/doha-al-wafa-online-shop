<?php

use App\Models\Faq;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\ContentSeeder;

/**
 * Build a complete page form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pagePayload(array $overrides = []): array
{
    return [
        'title_en' => 'Exchange and returns',
        'title_ar' => 'الاستبدال والإرجاع',
        'slug' => 'exchange-and-returns',
        'body_en' => "## Exchanges\n\nWithin **7 days**, unworn.",
        'body_ar' => "## الاستبدال\n\nخلال 7 أيام.",
        'sort_order' => '10',
        'is_published' => '1',
        ...$overrides,
    ];
}

it('adds a page', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->post(route('admin.pages.store'), pagePayload())
        ->assertRedirectToRoute('admin.pages.index');

    expect(Page::query()->sole()->slug)->toBe('exchange-and-returns');
});

it('checks the web address', function (string $slug) {
    Page::factory()->create(['slug' => 'privacy']);

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('admin.pages.store'), pagePayload(['slug' => $slug]))
        ->assertSessionHasErrors('slug');
})->with(['Exchange Returns', 'privacy', '-returns']);

it('updates and deletes a page', function () {
    $page = Page::factory()->create(['slug' => 'about']);
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->put(route('admin.pages.update', $page), pagePayload(['slug' => 'about', 'title_en' => 'Our story']))
        ->assertRedirectToRoute('admin.pages.index');
    expect($page->refresh()->title_en)->toBe('Our story');

    $this->delete(route('admin.pages.destroy', $page))->assertRedirectToRoute('admin.pages.index');
    $this->assertModelMissing($page);
});

it('adds, updates and deletes a question', function () {
    $owner = User::factory()->owner()->create();
    $payload = ['question_en' => 'How do I pay?', 'question_ar' => 'كيف أدفع؟', 'answer_en' => 'In cash.', 'answer_ar' => 'نقداً.', 'sort_order' => '10', 'is_published' => '1'];

    $this->actingAs($owner)->post(route('admin.faqs.store'), $payload)->assertRedirectToRoute('admin.faqs.index');
    $faq = Faq::query()->sole();

    $this->put(route('admin.faqs.update', $faq), [...$payload, 'answer_en' => 'Cash on delivery.'])->assertRedirectToRoute('admin.faqs.index');
    expect($faq->refresh()->answer_en)->toBe('Cash on delivery.');

    $this->delete(route('admin.faqs.destroy', $faq))->assertRedirectToRoute('admin.faqs.index');
    $this->assertModelMissing($faq);
});

it('requires both languages for a question', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->post(route('admin.faqs.store'), ['question_en' => 'How do I pay?', 'question_ar' => '', 'answer_en' => 'In cash.', 'answer_ar' => '', 'sort_order' => '0', 'is_published' => '1'])
        ->assertSessionHasErrors(['question_ar', 'answer_ar']);
});

it('seeds hidden draft pages and shown questions without overwriting edits', function () {
    $this->seed(ContentSeeder::class);
    Page::query()->where('slug', 'about')->update(['title_en' => 'Our story', 'is_published' => true]);

    $this->seed(ContentSeeder::class);

    expect(Page::query()->count())->toBe(5);
    expect(Page::query()->where('is_published', true)->pluck('slug')->all())->toBe(['about']);
    expect(Page::query()->where('slug', 'about')->value('title_en'))->toBe('Our story');
    expect(Faq::query()->where('is_published', true)->count())->toBe(5);
});
