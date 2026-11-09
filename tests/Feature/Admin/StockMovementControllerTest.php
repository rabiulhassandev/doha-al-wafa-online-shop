<?php

use App\Enums\StockMovementReason;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;

/**
 * Log a stock movement for a variant.
 */
function logMovement(ProductVariant $variant, StockMovementReason $reason, int $change): StockMovement
{
    return StockMovement::query()->create([
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'change' => $change,
        'stock_after' => max(0, $change),
        'reason' => $reason,
    ]);
}

it('lists stock changes and filters them by reason', function () {
    logMovement(ProductVariant::factory()->create(['sku' => 'DELIVERED-1']), StockMovementReason::Received, 5);
    logMovement(ProductVariant::factory()->create(['sku' => 'BROKEN-1']), StockMovementReason::Damaged, -1);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.stock-movements.index', ['reason' => 'damaged']))
        ->assertOk()
        ->assertSee('BROKEN-1')
        ->assertDontSee('DELIVERED-1');
});

it('keeps the history readable after a size is removed', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'GONE-56']);
    logMovement($variant, StockMovementReason::Received, 3);
    $variant->delete();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.stock-movements.index'))
        ->assertOk()
        ->assertSee('Removed item')
        ->assertSee('GONE-56');
});
