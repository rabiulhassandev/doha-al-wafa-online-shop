<?php

use App\Actions\AdjustStock;
use App\Enums\StockMovementReason;
use App\Exceptions\NotEnoughStockException;
use App\Models\ProductVariant;
use App\Models\User;

it('adds stock and logs who added it', function () {
    $variant = ProductVariant::factory()->inStock(3)->create(['sku' => 'DW0001-NAVY-56']);
    $owner = User::factory()->owner()->create();

    $movement = app(AdjustStock::class)->change($variant, 5, StockMovementReason::Received, $owner, 'Supplier delivery');

    expect($variant->refresh()->stock)->toBe(8);
    expect($movement->only(['sku', 'change', 'stock_after', 'note', 'user_id']))->toBe([
        'sku' => 'DW0001-NAVY-56',
        'change' => 5,
        'stock_after' => 8,
        'note' => 'Supplier delivery',
        'user_id' => $owner->id,
    ]);
    expect($movement->reason)->toBe(StockMovementReason::Received);
});

it('keeps the caller’s variant in step with the database', function () {
    $variant = ProductVariant::factory()->inStock(3)->create();

    app(AdjustStock::class)->change($variant, -2, StockMovementReason::Damaged);

    expect($variant->stock)->toBe(1);
    expect($variant->isDirty('stock'))->toBeFalse();
});

it('refuses to remove more than is in stock', function () {
    $variant = ProductVariant::factory()->inStock(2)->create();

    expect(fn () => app(AdjustStock::class)->change($variant, -3, StockMovementReason::Damaged))
        ->toThrow(NotEnoughStockException::class);

    expect($variant->refresh()->stock)->toBe(2);
    $this->assertDatabaseCount('stock_movements', 0);
});

it('stops at zero when asked to clamp', function () {
    $variant = ProductVariant::factory()->inStock(2)->create();

    $movement = app(AdjustStock::class)->change($variant, -5, StockMovementReason::StockGrid, clampAtZero: true);

    expect($variant->refresh()->stock)->toBe(0);
    expect($movement->change)->toBe(-2);
});

it('applies changes to the latest stock, not a stale copy', function () {
    $variant = ProductVariant::factory()->inStock(10)->create();
    $staleCopy = ProductVariant::query()->findOrFail($variant->id);
    app(AdjustStock::class)->change($variant, -4, StockMovementReason::Damaged);

    app(AdjustStock::class)->change($staleCopy, 1, StockMovementReason::Received);

    expect($variant->refresh()->stock)->toBe(7);
});

it('sets an exact count and logs the difference', function () {
    $variant = ProductVariant::factory()->inStock(10)->create();

    $movement = app(AdjustStock::class)->setTo($variant, 6, StockMovementReason::Recount);

    expect($variant->refresh()->stock)->toBe(6);
    expect($movement->change)->toBe(-4);
    expect($movement->stock_after)->toBe(6);
});

it('logs nothing when the stock does not change', function () {
    $variant = ProductVariant::factory()->inStock(4)->create();

    expect(app(AdjustStock::class)->setTo($variant, 4, StockMovementReason::Recount))->toBeNull();
    expect(app(AdjustStock::class)->change($variant, 0, StockMovementReason::Received))->toBeNull();

    $this->assertDatabaseCount('stock_movements', 0);
});
