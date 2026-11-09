<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private const STOCK_FILTERS = ['out' => 'Out of stock', 'low' => 'Low stock', 'in' => 'In stock'];

    /**
     * List the stock of every offered color and size, lowest first.
     */
    public function index(Request $request, Settings $settings): View
    {
        $search = trim((string) $request->query('search'));
        $stockFilter = array_key_exists((string) $request->query('stock'), self::STOCK_FILTERS) ? (string) $request->query('stock') : null;
        $lowStockThreshold = (int) $settings->get('low_stock_threshold');

        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->with(['product', 'productColor.color', 'size'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', fn (Builder $query) => $query
                        ->where('name_en', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%"));
            }))
            ->when($stockFilter === 'out', fn (Builder $query) => $query->where('stock', 0))
            ->when($stockFilter === 'low', fn (Builder $query) => $query->whereBetween('stock', [1, $lowStockThreshold]))
            ->when($stockFilter === 'in', fn (Builder $query) => $query->where('stock', '>', $lowStockThreshold))
            ->orderBy('stock')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.inventory.index', [
            'variants' => $variants,
            'search' => $search,
            'stockFilter' => $stockFilter,
            'stockFilterOptions' => self::STOCK_FILTERS,
            'lowStockThreshold' => $lowStockThreshold,
        ]);
    }
}
