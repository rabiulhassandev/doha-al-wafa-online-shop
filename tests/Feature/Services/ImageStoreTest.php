<?php

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
