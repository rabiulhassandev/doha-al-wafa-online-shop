<?php

use App\Models\Banner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Build a complete banner form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bannerPayload(array $overrides = []): array
{
    return [
        'title_en' => 'Eid is here',
        'title_ar' => 'العيد هنا',
        'subtitle_en' => '',
        'subtitle_ar' => '',
        'button_label_en' => 'Shop Eid',
        'button_label_ar' => 'تسوقي العيد',
        'link' => 'collection/eid',
        'starts_at' => '',
        'ends_at' => '',
        'sort_order' => '0',
        'is_active' => '1',
        ...$overrides,
    ];
}

it('adds a banner with desktop and phone images', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.banners.store'), bannerPayload([
            'image' => UploadedFile::fake()->image('wide.jpg', 2400, 1050),
            'mobile_image' => UploadedFile::fake()->image('tall.jpg', 1080, 1350),
        ]))
        ->assertRedirectToRoute('admin.banners.index')
        ->assertSessionHas('status', 'The banner was added.');

    $banner = Banner::query()->sole();
    expect($banner->image_path)->toStartWith('banners/');
    expect($banner->mobile_image_path)->toStartWith('banners/mobile/');
    Storage::disk('public')->assertExists([$banner->image_path, $banner->mobile_image_path]);
});

it('requires a desktop image for a new banner', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.banners.store'), bannerPayload())
        ->assertSessionHasErrors(['image' => 'Upload a desktop image.']);
});

it('accepts store paths and https links only', function (string $link, bool $valid) {
    Storage::fake('public');

    $response = $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.banners.store'), bannerPayload([
            'link' => $link,
            'image' => UploadedFile::fake()->image('wide.jpg', 1920, 840),
        ]));

    $valid
        ? $response->assertSessionHasNoErrors()
        : $response->assertSessionHasErrors(['link' => 'Use a store path such as collection/eid, or a full link starting with https://.']);
})->with([
    'store path' => ['shop?color=navy', true],
    'https link' => ['https://www.instagram.com/dohaalwafa', true],
    'javascript' => ['javascript:alert(1)', false],
    'plain http' => ['http://example.com', false],
]);

it('opens store paths in the shopper’s language', function () {
    $banner = Banner::factory()->make(['link' => 'collection/eid']);
    $external = Banner::factory()->make(['link' => 'https://www.instagram.com/dohaalwafa']);

    expect($banner->url('ar'))->toBe(url('ar/collection/eid'));
    expect($external->url('ar'))->toBe('https://www.instagram.com/dohaalwafa');
});

it('removes the phone image and its file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('banners/mobile/old.webp', 'image');
    $banner = Banner::factory()->create(['mobile_image_path' => 'banners/mobile/old.webp']);

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.banners.update', $banner), bannerPayload(['remove_mobile_image' => '1']))
        ->assertRedirectToRoute('admin.banners.index');

    expect($banner->refresh()->mobile_image_path)->toBeNull();
    Storage::disk('public')->assertMissing('banners/mobile/old.webp');
});

it('deletes a banner with its images', function () {
    Storage::fake('public');
    Storage::disk('public')->put('banners/wide.webp', 'image');
    $banner = Banner::factory()->create(['image_path' => 'banners/wide.webp']);

    $this->actingAs(User::factory()->staff()->create())
        ->delete(route('admin.banners.destroy', $banner))
        ->assertRedirectToRoute('admin.banners.index');

    $this->assertModelMissing($banner);
    Storage::disk('public')->assertMissing('banners/wide.webp');
});

it('forbids customers', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.banners.index'))
        ->assertForbidden();
});
