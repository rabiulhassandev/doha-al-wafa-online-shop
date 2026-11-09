<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImageFocus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageRequest;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Services\ImageStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    /**
     * Upload photos for a product color.
     *
     * The admin page sends one photo per request (already shrunk in the browser) and asks for JSON;
     * a plain form post with several photos also works.
     */
    public function store(ProductImageRequest $request, Product $product, ProductColor $color, ImageStore $images): JsonResponse|RedirectResponse
    {
        $focus = $request->enum('focus', ImageFocus::class);
        $sortOrder = (int) $color->images()->max('sort_order');

        /** @var list<UploadedFile> $files */
        $files = $request->file('images');

        foreach ($files as $file) {
            $directory = "products/{$product->id}/".Str::lower(Str::random(24));

            $color->images()->create([
                'directory' => $directory,
                'widths' => $images->storeProductImage($file->getRealPath(), $directory, $focus),
                'focus' => $focus,
                'sort_order' => $sortOrder += 10,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['uploaded' => count($files)], 201);
        }

        return redirect()
            ->to(route('admin.products.colors.index', $product)."#color-{$color->id}")
            ->with('status', count($files) === 1 ? 'The photo was uploaded.' : count($files).' photos were uploaded.');
    }

    /**
     * Delete a photo.
     */
    public function destroy(Product $product, ProductColor $color, ProductImage $image): RedirectResponse
    {
        $image->delete();

        return redirect()
            ->to(route('admin.products.colors.index', $product)."#color-{$color->id}")
            ->with('status', 'The photo was deleted.');
    }
}
