<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProductDefaultColorController extends Controller
{
    /**
     * Make a color the one shoppers see first.
     */
    public function __invoke(Product $product, ProductColor $color): RedirectResponse
    {
        DB::transaction(function () use ($product, $color): void {
            $product->colors()->whereKeyNot($color->id)->update(['is_default' => false]);
            $color->update(['is_default' => true]);
        });

        return redirect()
            ->to(route('admin.products.colors.index', $product)."#color-{$color->id}")
            ->with('status', "{$color->nameEn()} is now shown first.");
    }
}
