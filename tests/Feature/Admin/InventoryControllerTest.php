<?php

use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Settings;

it('lists offered sizes from lowest stock to highest', function () {
    ProductVariant::factory()->inStock(8)->create(['sku' => 'PLENTY']);
    ProductVariant::factory()->inStock(0)->create(['sku' => 'EMPTY']);
    ProductVariant::factory()->notOffered()->inStock(0)->create(['sku' => 'NOT-SOLD']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.inventory.index'))
        ->assertOk()
        ->assertSeeInOrder(['EMPTY', 'PLENTY'])
        ->assertDontSee('NOT-SOLD');
});

it('filters by stock level using the low stock setting', function (string $filter, array $visible, array $hidden) {
    app(Settings::class)->set(['low_stock_threshold' => 3]);
    ProductVariant::factory()->inStock(0)->create(['sku' => 'EMPTY']);
    ProductVariant::factory()->inStock(3)->create(['sku' => 'LOW']);
    ProductVariant::factory()->inStock(4)->create(['sku' => 'PLENTY']);

    $response = $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.inventory.index', ['stock' => $filter]))
        ->assertOk();

    foreach ($visible as $sku) {
        $response->assertSee($sku);
    }
    foreach ($hidden as $sku) {
        $response->assertDontSee($sku);
    }
})->with([
    'out of stock' => ['out', ['EMPTY'], ['LOW', 'PLENTY']],
    'low stock' => ['low', ['LOW'], ['EMPTY', 'PLENTY']],
    'in stock' => ['in', ['PLENTY'], ['EMPTY', 'LOW']],
]);

it('finds sizes by SKU or product name', function () {
    ProductVariant::factory()->create(['sku' => 'DW0001-NAVY-56']);
    ProductVariant::factory()->create(['sku' => 'DW0002-ROSE-54']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.inventory.index', ['search' => 'navy']))
        ->assertOk()
        ->assertSee('DW0001-NAVY-56')
        ->assertDontSee('DW0002-ROSE-54');
});
