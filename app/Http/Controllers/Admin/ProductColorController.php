<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateProductVariant;
use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Size;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductColorController extends Controller
{
    /**
     * Show the product's colors with their photos.
     */
    public function index(Product $product): View
    {
        $product->load(['colors.color', 'colors.images', 'variants']);

        return view('admin.products.colors', [
            'product' => $product,
            'availableColors' => Color::query()
                ->whereNotIn('id', $product->colors->pluck('color_id'))
                ->ordered()
                ->get(),
        ]);
    }

    /**
     * Add a palette color to the product, with the sizes the product already offers.
     */
    public function store(Request $request, Product $product, CreateProductVariant $createVariant): RedirectResponse
    {
        $validated = $request->validate([
            'color_id' => [
                'required',
                'integer',
                Rule::exists(Color::class, 'id'),
                Rule::unique(ProductColor::class)->where('product_id', $product->id),
            ],
        ], [
            'color_id.required' => 'Choose a color to add.',
            'color_id.unique' => 'This product already has that color.',
        ]);

        $productColor = DB::transaction(function () use ($product, $validated, $createVariant): ProductColor {
            $productColor = $product->colors()->create([
                'color_id' => $validated['color_id'],
                'sort_order' => ((int) $product->colors()->max('sort_order')) + 10,
                'is_default' => ! $product->colors()->exists(),
            ]);

            $offeredSizeIds = $product->variants()->where('is_active', true)->distinct()->pluck('size_id');

            Size::query()->findMany($offeredSizeIds)->each(
                fn (Size $size) => $createVariant->handle($productColor, $size),
            );

            return $productColor;
        });

        return redirect()
            ->to(route('admin.products.colors.index', $product)."#color-{$productColor->id}")
            ->with('status', "{$productColor->color->name_en} was added. Upload its photos below.");
    }

    /**
     * Update a product color's label and visibility.
     */
    public function update(Request $request, Product $product, ProductColor $color): RedirectResponse
    {
        $validated = $request->validate([
            'label_en' => ['nullable', 'string', 'max:60'],
            'label_ar' => ['nullable', 'string', 'max:60'],
            'is_active' => ['boolean'],
        ]);

        $color->update([...$validated, 'is_active' => $request->boolean('is_active')]);

        return redirect()
            ->to(route('admin.products.colors.index', $product)."#color-{$color->id}")
            ->with('status', "{$color->nameEn()} was saved.");
    }

    /**
     * Remove a color from the product, with its photos and sizes.
     */
    public function destroy(Product $product, ProductColor $color): RedirectResponse
    {
        DB::transaction(function () use ($product, $color): void {
            $color->delete();

            if ($color->is_default) {
                $product->colors()->first()?->update(['is_default' => true]);
            }
        });

        return to_route('admin.products.colors.index', $product)->with('status', "{$color->nameEn()} was removed from this product.");
    }
}
