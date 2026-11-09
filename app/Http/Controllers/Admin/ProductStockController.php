<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SyncProductStock;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductStockRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductStockController extends Controller
{
    /**
     * Show the colors × sizes stock grid.
     */
    public function edit(Product $product): View
    {
        $product->load(['colors.color', 'colors.images', 'variants.size', 'variants.productColor.color']);

        $variantsByCell = $product->variants->groupBy('product_color_id')
            ->map(fn ($variants) => $variants->keyBy('size_id'));

        return view('admin.products.stock', [
            'product' => $product,
            'sizes' => Size::query()->ordered()->get(),
            'variantsByCell' => $variantsByCell,
            'offeredSizeIds' => $product->variants->where('is_active', true)->pluck('size_id')->unique()->values()->all(),
            'offeredVariants' => $product->variants
                ->where('is_active', true)
                ->sortBy(fn (ProductVariant $variant): array => [$variant->productColor->sort_order, $variant->size->sort_order])
                ->values(),
        ]);
    }

    /**
     * Save the stock grid.
     */
    public function update(ProductStockRequest $request, Product $product, SyncProductStock $syncProductStock): RedirectResponse
    {
        $syncProductStock->handle($product, $request->sizeIds(), $request->cells(), $request->user());

        return to_route('admin.products.stock.edit', $product)->with('status', 'Sizes and stock were saved.');
    }
}
