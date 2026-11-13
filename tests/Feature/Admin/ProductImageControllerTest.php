<?php

use App\Enums\ImageFocus;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('store', function () {
    it('stores an uploaded photo at every product width and answers the uploader with JSON', function () {
        Storage::fake('public');
        $productColor = ProductColor::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->postJson(route('admin.products.colors.images.store', [$productColor->product, $productColor]), [
                'images' => [UploadedFile::fake()->image('navy.jpg', 1600, 2000)],
                'focus' => 'top',
            ])
            ->assertCreated()
            ->assertExactJson(['uploaded' => 1]);

        $image = ProductImage::query()->sole();
        expect($image->product_color_id)->toBe($productColor->id);
        expect($image->widths)->toBe([1200, 800, 400]);
        expect($image->focus)->toBe(ImageFocus::Top);
        expect($image->directory)->toStartWith("products/{$productColor->product_id}/");
        Storage::disk('public')->assertExists([
            "{$image->directory}/source.webp",
            "{$image->directory}/1200.webp",
            "{$image->directory}/800.webp",
            "{$image->directory}/400.webp",
        ]);
    });

    it('adds new photos after the existing ones', function () {
        Storage::fake('public');
        $productColor = ProductColor::factory()->create();
        ProductImage::factory()->for($productColor)->create(['sort_order' => 40]);

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.colors.images.store', [$productColor->product, $productColor]), [
                'images' => [UploadedFile::fake()->image('a.jpg', 800, 1000), UploadedFile::fake()->image('b.jpg', 800, 1000)],
                'focus' => 'center',
            ])
            ->assertSessionHas('status', '2 photos were uploaded.');

        expect($productColor->images()->pluck('sort_order')->all())->toBe([40, 50, 60]);
    });

    it('rejects photos outside the allowed dimensions', function (int $width, int $height) {
        Storage::fake('public');
        $productColor = ProductColor::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->postJson(route('admin.products.colors.images.store', [$productColor->product, $productColor]), [
                'images' => [UploadedFile::fake()->image('photo.jpg', $width, $height)],
                'focus' => 'center',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['images.0' => 'Photos must be at least 500 × 625 pixels and at most 4096 pixels on either side.']);

        $this->assertDatabaseCount('product_images', 0);
    })->with(['too small' => [300, 300], 'too wide' => [4097, 625], 'too tall' => [500, 4097]]);

    it('stops at twelve photos per color', function () {
        Storage::fake('public');
        $productColor = ProductColor::factory()->create();
        ProductImage::factory()->for($productColor)->count(12)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->postJson(route('admin.products.colors.images.store', [$productColor->product, $productColor]), [
                'images' => [UploadedFile::fake()->image('one-more.jpg', 800, 1000)],
                'focus' => 'center',
            ])
            ->assertJsonValidationErrors(['images' => 'Each color can have up to 12 photos.']);
    });
});

describe('destroy', function () {
    it('deletes the photo and its files', function () {
        Storage::fake('public');
        $image = ProductImage::factory()->create(['directory' => 'products/1/photo']);
        Storage::disk('public')->put('products/1/photo/800.webp', 'photo');
        $productColor = $image->productColor;

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.products.colors.images.destroy', [$productColor->product, $productColor, $image]))
            ->assertRedirect();

        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing('products/1/photo/800.webp');
    });

    it('returns 404 for a photo of another color', function () {
        $image = ProductImage::factory()->create();
        $otherColor = ProductColor::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.products.colors.images.destroy', [$otherColor->product, $otherColor, $image]))
            ->assertNotFound();

        $this->assertModelExists($image);
    });
});

it('moves a photo earlier so it becomes the cover', function () {
    $productColor = ProductColor::factory()->create();
    $cover = ProductImage::factory()->for($productColor)->create(['sort_order' => 10]);
    $second = ProductImage::factory()->for($productColor)->create(['sort_order' => 20]);

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.products.colors.images.position', [$productColor->product, $productColor, $second]), ['direction' => 'up']);

    expect($productColor->images()->pluck('id')->all())->toBe([$second->id, $cover->id]);
});
