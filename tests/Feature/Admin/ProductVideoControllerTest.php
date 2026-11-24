<?php

use App\Models\ProductColor;
use App\Models\ProductVideo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

it('uploads a video and shows it in the admin and storefront galleries', function (string $extension, string $mime) {
    $product = listedProduct();
    $gallery = $product->colors->first();

    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.products.colors.videos.store', [$product, $gallery]), [
            'video' => UploadedFile::fake()->create('clip.'.$extension, 100, $mime),
        ])
        ->assertRedirect(route('admin.products.colors.index', $product).'#color-'.$gallery->id)
        ->assertSessionHas('status', 'The video was uploaded.');

    $video = ProductVideo::query()->sole();
    expect($video->product_color_id)->toBe($gallery->id);
    expect($video->mime_type)->toBe($mime);
    Storage::disk('public')->assertExists($video->path);

    $this->get(route('admin.products.colors.index', $product))->assertOk()->assertSee($video->url());
    $this->get(route('products.show', ['locale' => 'en', 'product' => $product]))
        ->assertOk()
        ->assertSee('controls muted loop playsinline preload="none"', false)
        ->assertSee(' autoplay ', false)
        ->assertSeeInOrder(['data-video-slide="0"', $video->url(), 'Enlarge photo 1'], false)
        ->assertSeeInOrder(['Show video 1', 'Show photo 1'])
        ->assertSee($video->url())
        ->assertViewHas('pageData', fn (array $data): bool => $data['colors'][$gallery->color->slug]['mediaCount'] === $gallery->images->count() + 1
        );
})->with(['MP4' => ['mp4', 'video/mp4'], 'WebM' => ['webm', 'video/webm']]);

it('rejects unsupported, oversized or missing video uploads', function (string $case) {
    $gallery = ProductColor::factory()->create();
    $disguised = UploadedFile::fake()->createWithContent('clip.mp4', '<?php echo "not a video";');
    $file = match ($case) {
        'oversized' => UploadedFile::fake()->create('clip.mp4', 30721, 'video/mp4'),
        'unsupported' => UploadedFile::fake()->create('clip.mov', 10, 'video/quicktime'),
        'disguised' => new UploadedFile($disguised->getPathname(), 'clip.mp4', test: true),
        'missing' => null,
    };

    $this->actingAs(User::factory()->staff()->create())
        ->postJson(route('admin.products.colors.videos.store', [$gallery->product, $gallery]), ['video' => $file])
        ->assertUnprocessable()->assertJsonValidationErrors('video');

    $this->assertDatabaseCount('product_videos', 0);
    expect(Storage::disk('public')->allFiles())->toBe([]);
})->with(['oversized', 'unsupported', 'disguised', 'missing']);

it('caps gallery uploads at three and removes the rejected file', function () {
    $gallery = ProductColor::factory()->create();
    ProductVideo::factory()->for($gallery)->count(3)->create();

    $this->actingAs(User::factory()->staff()->create())
        ->postJson(route('admin.products.colors.videos.store', [$gallery->product, $gallery]), [
            'video' => UploadedFile::fake()->create('extra.mp4', 10, 'video/mp4'),
        ])->assertUnprocessable()->assertJsonValidationErrors('video');

    $this->assertDatabaseCount('product_videos', 3);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('allows only catalog staff to upload videos', function (bool $signedIn) {
    $gallery = ProductColor::factory()->create();
    if ($signedIn) {
        $this->actingAs(User::factory()->create());
    }

    $response = $this->postJson(route('admin.products.colors.videos.store', [$gallery->product, $gallery]), [
        'video' => UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'),
    ]);
    $response->assertStatus($signedIn ? 403 : 401);
    $this->assertDatabaseCount('product_videos', 0);
})->with([true, false]);

it('scopes video uploads and deletion to the correct product and gallery', function () {
    $video = ProductVideo::factory()->create();
    $other = ProductColor::factory()->create();
    $this->actingAs(User::factory()->staff()->create())
        ->postJson(route('admin.products.colors.videos.store', [$other->product, $video->productColor]), [
            'video' => UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'),
        ])->assertNotFound();
    $this->delete(route('admin.products.colors.videos.destroy', [$other->product, $other, $video]))->assertNotFound();
    $this->assertModelExists($video);
});

it('shows videos in the shared gallery of a product without colors', function () {
    $product = listedProduct();
    $gallery = $product->colors()->first();
    $gallery->update(['color_id' => null]);
    $video = ProductVideo::factory()->for($gallery)->create();

    $this->get(route('products.show', ['locale' => 'ar', 'product' => $product]))
        ->assertOk()
        ->assertSee($video->url())
        ->assertSee('عرض الفيديو 1')
        ->assertViewHas('pageData', fn (array $data): bool => $data['colors']['']['mediaCount'] === 2);
});

it('deletes video files when the video, its gallery or its product is deleted', function (string $target) {
    $video = ProductVideo::factory()->create();
    Storage::disk('public')->put($video->path, 'video');
    $gallery = $video->productColor;
    $product = $gallery->product;
    $route = match ($target) {
        'video' => route('admin.products.colors.videos.destroy', [$product, $gallery, $video]),
        'gallery' => route('admin.products.colors.destroy', [$product, $gallery]),
        'product' => route('admin.products.destroy', $product),
    };

    $this->actingAs(User::factory()->staff()->create())->delete($route)->assertRedirect();
    $this->assertModelMissing($video);
    Storage::disk('public')->assertMissing($video->path);
})->with(['video', 'gallery', 'product']);
