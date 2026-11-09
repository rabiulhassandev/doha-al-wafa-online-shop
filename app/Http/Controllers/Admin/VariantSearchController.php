<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Support\VariantOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Finds colors and sizes on sale by SKU, product code or product name, for adding to orders.
 */
class VariantSearchController extends Controller
{
    private const MAX_RESULTS = 20;

    public function __invoke(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q'));

        if (mb_strlen($search) < 2) {
            return response()->json(['variants' => []]);
        }

        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->whereRelation('product', 'status', ProductStatus::Active)
            ->whereRelation('productColor', 'is_active', true)
            ->where(fn (Builder $query) => $query
                ->where('sku', 'like', "%{$search}%")
                ->orWhereHas('product', fn (Builder $query) => $query
                    ->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")))
            ->with(['product', 'productColor.color', 'productColor.images', 'size'])
            ->orderByDesc('stock')
            ->orderBy('id')
            ->limit(self::MAX_RESULTS)
            ->get();

        return response()->json([
            'variants' => $variants->map(fn (ProductVariant $variant): array => VariantOption::from($variant))->values(),
        ]);
    }
}
