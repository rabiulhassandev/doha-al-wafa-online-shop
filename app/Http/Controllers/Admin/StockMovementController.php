<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementReason;
use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * Show the log of every stock change, newest first.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $reason = StockMovementReason::tryFrom((string) $request->query('reason'));

        $movements = StockMovement::query()
            ->with(['user', 'productVariant.product', 'productVariant.productColor.color', 'productVariant.size'])
            ->when($search !== '', fn (Builder $query) => $query->where('sku', 'like', "%{$search}%"))
            ->when($reason !== null, fn (Builder $query) => $query->where('reason', $reason))
            ->latest()
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.stock-movements.index', [
            'movements' => $movements,
            'search' => $search,
            'reason' => $reason,
            'reasonOptions' => StockMovementReason::options(),
        ]);
    }
}
