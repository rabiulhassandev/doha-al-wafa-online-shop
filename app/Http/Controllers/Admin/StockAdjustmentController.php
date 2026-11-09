<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AdjustStock;
use App\Enums\StockMovementReason;
use App\Exceptions\NotEnoughStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockAdjustmentRequest;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StockAdjustmentController extends Controller
{
    /**
     * Show the stock adjustment form and recent history for one color and size.
     */
    public function create(ProductVariant $variant): View
    {
        $variant->load(['product', 'productColor.color', 'size']);

        return view('admin.inventory.adjust', [
            'variant' => $variant,
            'movements' => $variant->stockMovements()->with('user')->latest()->orderByDesc('id')->limit(20)->get(),
            'reasonOptions' => collect(StockAdjustmentRequest::MANUAL_REASONS)
                ->mapWithKeys(fn (StockMovementReason $reason): array => [$reason->value => $reason->label()])
                ->all(),
        ]);
    }

    /**
     * Record new stock, damaged stock or a recount.
     */
    public function store(StockAdjustmentRequest $request, ProductVariant $variant, AdjustStock $adjustStock): RedirectResponse
    {
        $reason = $request->enum('reason', StockMovementReason::class);
        $quantity = $request->integer('quantity');
        $note = $request->validated('note');

        try {
            match ($reason) {
                StockMovementReason::Received => $adjustStock->change($variant, $quantity, $reason, $request->user(), $note),
                StockMovementReason::Damaged => $adjustStock->change($variant, -$quantity, $reason, $request->user(), $note),
                default => $adjustStock->setTo($variant, $quantity, $reason, $request->user(), $note),
            };
        } catch (NotEnoughStockException $exception) {
            return back()
                ->withInput()
                ->withErrors(['quantity' => "Only {$exception->variant->stock} in stock, so you can remove at most {$exception->variant->stock}."]);
        }

        return to_route('admin.inventory.adjust', $variant)->with('status', "Stock for {$variant->sku} is now {$variant->stock}.");
    }
}
