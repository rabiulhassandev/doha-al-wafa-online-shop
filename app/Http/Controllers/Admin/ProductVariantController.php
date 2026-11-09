<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantsRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProductVariantController extends Controller
{
    /**
     * Save the SKU and optional price of each size.
     */
    public function update(ProductVariantsRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            $variants = $product->variants()->get()->keyBy('id');

            foreach ($request->variantAttributes() as $variantId => $attributes) {
                $variants[$variantId]->update($attributes);
            }
        });

        return redirect()
            ->to(route('admin.products.stock.edit', $product).'#skus-and-prices')
            ->with('status', 'SKUs and prices were saved.');
    }
}
