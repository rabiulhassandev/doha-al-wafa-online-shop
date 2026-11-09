<?php

use App\Actions\MoveInList;
use App\Models\Product;
use App\Models\ProductColor;

it('swaps a record with its neighbour and renumbers the list', function (string $direction, array $expectedOrder) {
    $product = Product::factory()->create();
    $first = ProductColor::factory()->for($product)->create(['sort_order' => 5]);
    $second = ProductColor::factory()->for($product)->create(['sort_order' => 7]);
    $third = ProductColor::factory()->for($product)->create(['sort_order' => 30]);
    $names = [$first->id => 'first', $second->id => 'second', $third->id => 'third'];

    app(MoveInList::class)->handle($product->colors()->get(), $second, $direction);

    $order = $product->colors()->get()->map(fn (ProductColor $color): string => $names[$color->id])->all();
    expect($order)->toBe($expectedOrder);
    expect($product->colors()->pluck('sort_order')->all())->toBe([10, 20, 30]);
})->with([
    'up' => ['up', ['second', 'first', 'third']],
    'down' => ['down', ['first', 'third', 'second']],
]);

it('leaves the list alone when the record is already at the end', function () {
    $product = Product::factory()->create();
    $first = ProductColor::factory()->for($product)->create(['sort_order' => 1]);
    ProductColor::factory()->for($product)->create(['sort_order' => 2]);

    app(MoveInList::class)->handle($product->colors()->get(), $first, 'up');

    expect($product->colors()->pluck('sort_order')->all())->toBe([1, 2]);
});
