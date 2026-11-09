<?php

namespace App\Http\Controllers\Admin;

use App\Actions\MoveInList;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductColorPositionController extends Controller
{
    /**
     * Move a product color one place up or down.
     */
    public function __invoke(Request $request, Product $product, ProductColor $color, MoveInList $moveInList): RedirectResponse
    {
        $validated = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);

        $moveInList->handle($product->colors()->get(), $color, $validated['direction']);

        return redirect()->to(route('admin.products.colors.index', $product)."#color-{$color->id}");
    }
}
