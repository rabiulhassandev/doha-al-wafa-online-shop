<?php

use App\Enums\StockMovementReason;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;

it('shows the adjustment form with recent changes', function () {
    $variant = ProductVariant::factory()->inStock(4)->create();
    StockMovement::query()->create([
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'change' => 4,
        'stock_after' => 4,
        'reason' => StockMovementReason::Received,
        'note' => 'First delivery',
    ]);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.inventory.adjust', $variant))
        ->assertOk()
        ->assertSee('First delivery');
});

it('applies each kind of adjustment', function (string $reason, int $quantity, int $expectedStock) {
    $variant = ProductVariant::factory()->inStock(5)->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->post(route('admin.inventory.adjustments.store', $variant), ['reason' => $reason, 'quantity' => (string) $quantity, 'note' => 'Checked'])
        ->assertRedirectToRoute('admin.inventory.adjust', $variant)
        ->assertSessionHas('status', "Stock for {$variant->sku} is now {$expectedStock}.");

    expect($variant->refresh()->stock)->toBe($expectedStock);
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant->id,
        'reason' => $reason,
        'stock_after' => $expectedStock,
        'note' => 'Checked',
        'user_id' => $staff->id,
    ]);
})->with([
    'received adds' => ['received', 3, 8],
    'damaged removes' => ['damaged', 2, 3],
    'recount replaces' => ['recount', 12, 12],
    'recount to zero' => ['recount', 0, 0],
]);

it('refuses to remove more than is in stock', function () {
    $variant = ProductVariant::factory()->inStock(2)->create();

    $this->actingAs(User::factory()->staff()->create())
        ->from(route('admin.inventory.adjust', $variant))
        ->post(route('admin.inventory.adjustments.store', $variant), ['reason' => 'damaged', 'quantity' => '3'])
        ->assertRedirectToRoute('admin.inventory.adjust', $variant)
        ->assertSessionHasErrors(['quantity' => 'Only 2 in stock, so you can remove at most 2.']);

    expect($variant->refresh()->stock)->toBe(2);
});

it('only accepts the reasons a person can choose', function () {
    $variant = ProductVariant::factory()->inStock(2)->create();

    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.inventory.adjustments.store', $variant), ['reason' => 'stock_grid', 'quantity' => '1'])
        ->assertSessionHasErrors(['reason' => 'The selected reason is invalid.']);
});

it('requires a quantity of at least one when adding or removing', function () {
    $variant = ProductVariant::factory()->inStock(2)->create();

    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.inventory.adjustments.store', $variant), ['reason' => 'received', 'quantity' => '0'])
        ->assertSessionHasErrors(['quantity' => 'Enter a quantity of at least 1.']);
});
