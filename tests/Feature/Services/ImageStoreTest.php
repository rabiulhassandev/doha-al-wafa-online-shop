<?php

use App\Enums\ImageFocus;
use App\Enums\ImagePreset;
use App\Services\ImageStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Read the pixel size of a stored image.
 *
 * @return array{int, int}
 */
function storedImageSize(string $path): array
{
    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

    return [$width, $height];
}

it('scales wide images down to the preset width and keeps the proportions', function () {
    Storage::fake('public');

    $path = app(ImageStore::class)->store(UploadedFile::fake()->image('banner.jpg', 3000, 1000), ImagePreset::CollectionBanner);

    expect(storedImageSize($path))->toBe([1920, 640]);
});

it('does not enlarge small images', function () {
    Storage::fake('public');

    $path = app(ImageStore::class)->store(UploadedFile::fake()->image('small.jpg', 400, 300), ImagePreset::CategoryImage);

    expect(storedImageSize($path))->toBe([400, 300]);
});

it('stores images as WebP in the preset folder', function () {
    Storage::fake('public');

    $path = app(ImageStore::class)->store(UploadedFile::fake()->image('photo.png', 500, 500), ImagePreset::CategoryImage);

    expect($path)->toStartWith('categories/')->toEndWith('.webp');
    expect(Storage::disk('public')->mimeType($path))->toBe('image/webp');
});

it('crops product photos to 4:5 at every product width and keeps an uncropped source', function () {
    Storage::fake('public');
    $photo = UploadedFile::fake()->image('portrait.jpg', 2400, 3200);

    $widths = app(ImageStore::class)->storeProductImage($photo->getRealPath(), 'products/1/abc', ImageFocus::Center);

    expect($widths)->toBe([1200, 800, 400]);
    expect(storedImageSize('products/1/abc/1200.webp'))->toBe([1200, 1500]);
    expect(storedImageSize('products/1/abc/800.webp'))->toBe([800, 1000]);
    expect(storedImageSize('products/1/abc/400.webp'))->toBe([400, 500]);
    expect(storedImageSize('products/1/abc/source.webp'))->toBe([1500, 2000]);
});

it('crops landscape photos to the 4:5 area that fits inside them', function () {
    Storage::fake('public');
    $photo = UploadedFile::fake()->image('landscape.jpg', 3000, 2000);

    $widths = app(ImageStore::class)->storeProductImage($photo->getRealPath(), 'products/1/wide', ImageFocus::Center);

    expect($widths)->toBe([800, 400]);
    expect(storedImageSize('products/1/wide/800.webp'))->toBe([800, 1000]);
});

it('skips product widths larger than a small photo', function () {
    Storage::fake('public');
    $photo = UploadedFile::fake()->image('small.jpg', 700, 875);

    $widths = app(ImageStore::class)->storeProductImage($photo->getRealPath(), 'products/1/small', ImageFocus::Top);

    expect($widths)->toBe([400]);
    Storage::disk('public')->assertMissing('products/1/small/800.webp');
});

it('keeps the current image when nothing new is submitted', function () {
    $path = app(ImageStore::class)->resolve('categories/current.webp', null, false, ImagePreset::CategoryImage);

    expect($path)->toBe('categories/current.webp');
});

it('clears the image when removal is requested', function () {
    $path = app(ImageStore::class)->resolve('categories/current.webp', null, true, ImagePreset::CategoryImage);

    expect($path)->toBeNull();
});

it('prefers a new upload over the remove checkbox', function () {
    Storage::fake('public');

    $path = app(ImageStore::class)->resolve('categories/current.webp', UploadedFile::fake()->image('new.jpg'), true, ImagePreset::CategoryImage);

    expect($path)->not->toBe('categories/current.webp')->toStartWith('categories/');
});
