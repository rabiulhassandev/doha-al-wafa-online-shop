<?php

namespace App\Http\Controllers\Admin;

use App\Actions\MoveInList;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductImagePositionController extends Controller
{
    /**
     * Move a photo one place earlier ("up") or later ("down"). The first photo is the color's cover.
     */
    public function __invoke(Request $request, Product $product, ProductColor $color, ProductImage $image, MoveInList $moveInList): RedirectResponse
    {
        $validated = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);

        $moveInList->handle($color->images()->get(), $image, $validated['direction']);

        return redirect()->to(route('admin.products.colors.index', $product)."#color-{$color->id}");
    }
}
