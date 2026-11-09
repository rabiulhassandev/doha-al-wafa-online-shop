<?php

use App\Actions\CollectCashPayment;
use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\OrderUpdateException;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;

/**
 * Move an order through the given statuses as the team would.
 */
function moveOrderThrough(Order $order, OrderStatus ...$statuses): void
{
    foreach ($statuses as $status) {
        app(UpdateOrderStatus::class)->handle($order, $status, null, $status === OrderStatus::Cancelled ? 'Customer changed their mind' : null);
    }
}

it('takes an order from waiting for confirmation to delivered, recording each step', function () {
    $user = User::factory()->staff()->create(['name' => 'Mariam']);
    $order = placeOrder([listedProduct()->variants()->sole()->id => 1]);

    foreach ([OrderStatus::Confirmed, OrderStatus::Packed, OrderStatus::OutForDelivery, OrderStatus::Delivered] as $status) {
        app(UpdateOrderStatus::class)->handle($order, $status, $user, "Now {$status->value}");
    }

    expect($order->status)->toBe(OrderStatus::Delivered);
    $history = $order->statusHistories()->get();
    expect($history->pluck('to_status')->all())->toBe([OrderStatus::Confirmed, OrderStatus::Packed, OrderStatus::OutForDelivery, OrderStatus::Delivered]);
    expect($history->first()->from_status)->toBe(OrderStatus::PendingConfirmation);
    expect($history->last()->note)->toBe('Now delivered');
    expect($history->pluck('user_id')->unique()->all())->toBe([$user->id]);
});

it('puts the exact colors and sizes back in stock when an order is cancelled', function () {
    $product = listedProduct(stockByColor: ['navy' => ['54' => 4, '56' => 3]]);
    $navy54 = $product->variants()->whereRelation('size', 'slug', '54')->sole();
    $navy56 = $product->variants()->whereRelation('size', 'slug', '56')->sole();
    $order = placeOrder([$navy54->id => 1, $navy56->id => 2]);

    moveOrderThrough($order, OrderStatus::Cancelled);

    expect([$navy54->refresh()->stock, $navy56->refresh()->stock])->toBe([4, 3]);
    $restock = StockMovement::query()->where('reason', StockMovementReason::OrderCancelled)->orderBy('id')->get();
    expect($restock->pluck('change')->all())->toEqualCanonicalizing([1, 2]);
    expect($restock->pluck('order_id')->unique()->all())->toBe([$order->id]);
    expect($order->statusHistories()->sole()->note)->toBe('Customer changed their mind');
});

it('puts pieces back in stock when a delivered order is returned', function () {
    $variant = listedProduct(stockByColor: ['navy' => ['56' => 3]])->variants()->sole();
    $order = placeOrder([$variant->id => 2]);

    moveOrderThrough($order, OrderStatus::Confirmed, OrderStatus::OutForDelivery, OrderStatus::Delivered, OrderStatus::Returned);

    expect($variant->refresh()->stock)->toBe(3);
    expect(StockMovement::query()->where('reason', StockMovementReason::OrderReturned)->sole()->change)->toBe(2);
});

it('refuses status changes the order cannot make', function (array $before, OrderStatus $next) {
    $variant = listedProduct(stockByColor: ['navy' => ['56' => 3]])->variants()->sole();
    $order = placeOrder([$variant->id => 1]);
    moveOrderThrough($order, ...$before);
    $historyCount = $order->statusHistories()->count();

    expect(fn () => app(UpdateOrderStatus::class)->handle($order, $next, null, 'Again'))->toThrow(OrderUpdateException::class);

    expect($order->statusHistories()->count())->toBe($historyCount);
    expect($variant->refresh()->stock)->toBe(OrderStatus::Cancelled === ($before[0] ?? null) ? 3 : 2);
})->with([
    'cancel a cancelled order twice' => [[OrderStatus::Cancelled], OrderStatus::Cancelled],
    'deliver an order not sent out' => [[OrderStatus::Confirmed], OrderStatus::Delivered],
    'cancel a delivered order' => [[OrderStatus::Confirmed, OrderStatus::OutForDelivery, OrderStatus::Delivered], OrderStatus::Cancelled],
    'return an order not delivered' => [[], OrderStatus::Returned],
]);

it('restocks only the lines whose color and size still exist', function () {
    $kept = listedProduct(['name_en' => 'Kept'], ['navy' => ['56' => 3]])->variants()->sole();
    $deleted = listedProduct(['name_en' => 'Deleted'], ['olive' => ['58' => 3]])->variants()->sole();
    $order = placeOrder([$kept->id => 1, $deleted->id => 1]);
    $deleted->delete();

    moveOrderThrough($order, OrderStatus::Cancelled);

    expect($kept->refresh()->stock)->toBe(3);
    expect($order->status)->toBe(OrderStatus::Cancelled);
});

it('records the cash as collected when marking delivered', function () {
    $order = placeOrder([listedProduct(['price' => 18500])->variants()->sole()->id => 1]);
    moveOrderThrough($order, OrderStatus::Confirmed, OrderStatus::OutForDelivery);

    app(UpdateOrderStatus::class)->handle($order, OrderStatus::Delivered, null, cashCollected: true);

    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    $payment = $order->payments()->sole();
    expect($payment->status)->toBe(PaymentStatus::Paid);
    expect($payment->paid_at)->not->toBeNull();
});

it('collects cash only once an order has left with the driver', function () {
    $order = placeOrder([listedProduct()->variants()->sole()->id => 1]);

    expect(fn () => app(CollectCashPayment::class)->handle($order))->toThrow(OrderUpdateException::class);

    moveOrderThrough($order, OrderStatus::Confirmed, OrderStatus::OutForDelivery);
    app(CollectCashPayment::class)->handle($order);

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
    expect(fn () => app(CollectCashPayment::class)->handle($order))->toThrow(OrderUpdateException::class);
});
