<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVideoRequest;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProductVideoController extends Controller
{
    public function store(ProductVideoRequest $request, Product $product, ProductColor $color): RedirectResponse
    {
        $file = $request->file('video');
        $disk = Storage::disk(config('shop.media_disk'));
        $path = $disk->putFile("products/{$product->id}/videos", $file);

        if ($path === false) {
            throw new RuntimeException('The video could not be stored.');
        }

        try {
            DB::transaction(function () use ($color, $path, $file): void {
                ProductColor::query()->lockForUpdate()->findOrFail($color->id);

                if ($color->videos()->count() >= ProductVideoRequest::MAX_VIDEOS_PER_GALLERY) {
                    throw ValidationException::withMessages(['video' => 'Each gallery can have up to 3 videos.']);
                }

                $color->videos()->create(['path' => $path, 'mime_type' => $file->getMimeType()]);
            });
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }

        return redirect()->to(route('admin.products.colors.index', $product)."#color-{$color->id}")
            ->with('status', 'The video was uploaded.');
    }

    public function destroy(Product $product, ProductColor $color, ProductVideo $video): RedirectResponse
    {
        $video->delete();

        return redirect()->to(route('admin.products.colors.index', $product)."#color-{$color->id}")
            ->with('status', 'The video was deleted.');
    }
}
