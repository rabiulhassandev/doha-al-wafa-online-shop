<?php

use App\Actions\UpdateOrderItems;
use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\OrderUpdateException;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;

/**
 * Change an order's lines as a team member.
 *
 * @param  array<int, int>  $quantities  keyed by order item ID
 * @param  array<int, int>  $additions  keyed by product variant ID
 */
function changeOrderItems(Order $order, array $quantities = [], array $additions = [], int $deliveryFee = 2500): void
{
    app(UpdateOrderItems::class)->handle($order, $quantities, $additions, $deliveryFee, User::factory()->staff()->create());
}

it('takes more stock when a quantity goes up and returns it when it goes down', function () {
    $variant = listedProduct(['price' => 10000], ['navy' => ['56' => 5]])->variants()->sole();
    $order = placeOrder([$variant->id => 2]);
    $item = $order->items()->sole();

    changeOrderItems($order, [$item->id => 4]);
    expect($variant->refresh()->stock)->toBe(1);

    changeOrderItems($order, [$item->id => 1]);
    expect($variant->refresh()->stock)->toBe(4);

    expect([$item->refresh()->quantity, $item->line_total])->toBe([1, 10000]);
    expect([$order->subtotal, $order->grand_total])->toBe([10000, 12500]);
    expect(StockMovement::query()->where('reason', StockMovementReason::OrderEdited)->pluck('change')->all())->toBe([-2, 3]);
});

it('removes a line set to zero and returns its stock', function () {
    $kept = listedProduct(['price' => 10000], ['navy' => ['56' => 5]])->variants()->sole();
    $removed = listedProduct(['price' => 5000], ['olive' => ['58' => 5]])->variants()->sole();
    $order = placeOrder([$kept->id => 1, $removed->id => 2]);

    changeOrderItems($order, [$order->items()->where('product_variant_id', $removed->id)->value('id') => 0]);

    expect($order->items()->pluck('product_variant_id')->all())->toBe([$kept->id]);
    expect($removed->refresh()->stock)->toBe(5);
    expect($order->subtotal)->toBe(10000);
});

it('adds a color and size at today’s price and keeps sold lines at their price', function () {
    $sold = listedProduct(['price' => 10000], ['navy' => ['56' => 5]])->variants()->sole();
    $order = placeOrder([$sold->id => 1]);
    $sold->product->update(['price' => 12000]);
    $added = listedProduct(['price' => 8000], ['olive' => ['58' => 5]])->variants()->sole();

    changeOrderItems($order, additions: [$added->id => 2], deliveryFee: 0);

    expect($order->items()->where('product_variant_id', $sold->id)->value('unit_price'))->toBe(10000);
    expect($order->items()->where('product_variant_id', $added->id)->value('line_total'))->toBe(16000);
    expect($added->refresh()->stock)->toBe(3);
    expect([$order->subtotal, $order->delivery_fee, $order->grand_total])->toBe([26000, 0, 26000]);
    expect($order->payments()->sole()->amount)->toBe(26000);
});

it('raises the existing line when adding a color and size already in the order', function () {
    $variant = listedProduct(stockByColor: ['navy' => ['56' => 5]])->variants()->sole();
    $order = placeOrder([$variant->id => 1]);

    changeOrderItems($order, additions: [$variant->id => 2]);

    expect($order->items()->sole()->quantity)->toBe(3);
    expect($variant->refresh()->stock)->toBe(2);
});

it('lets the team order past the shopper limit, up to the stock left', function () {
    $variant = listedProduct(stockByColor: ['navy' => ['56' => 30]])->variants()->sole();
    $order = placeOrder([$variant->id => 1]);

    changeOrderItems($order, [$order->items()->sole()->id => 15]);

    expect($order->items()->sole()->quantity)->toBe(15);
});

it('refuses changes it cannot make and leaves the order as it was', function (Closure $change, string $message) {
    $variant = listedProduct(['price' => 10000], ['navy' => ['56' => 3]])->variants()->sole();
    $order = placeOrder([$variant->id => 2]);

    expect(fn () => $change($order))->toThrow(OrderUpdateException::class, $message);

    expect($variant->refresh()->stock)->toBe(1);
    expect($order->refresh()->items()->sole()->quantity)->toBe(2);
    expect($order->grand_total)->toBe(22500);
})->with([
    'more than is in stock' => [fn (Order $order) => changeOrderItems($order, [$order->items()->sole()->id => 4]), 'Only 1 of'],
    'no items left' => [fn (Order $order) => changeOrderItems($order, [$order->items()->sole()->id => 0]), 'An order needs at least one item.'],
    'a draft product' => [fn (Order $order) => changeOrderItems($order, additions: [listedProduct(['status' => 'draft'])->variants()->sole()->id => 1]), 'is not for sale right now'],
    'after it left with the driver' => [function (Order $order) {
        app(UpdateOrderStatus::class)->handle($order, OrderStatus::Confirmed, null);
        app(UpdateOrderStatus::class)->handle($order, OrderStatus::OutForDelivery, null);
        changeOrderItems($order, [$order->items()->sole()->id => 1]);
    }, 'Items can only change before an order goes out for delivery.'],
]);
