<?php

use App\Enums\OrderStatus;

it('only lets an order move forward along its journey', function (OrderStatus $from, OrderStatus $to, bool $allowed) {
    expect($from->canBecome($to))->toBe($allowed);
})->with([
    'confirm a new order' => [OrderStatus::PendingConfirmation, OrderStatus::Confirmed, true],
    'skip packing' => [OrderStatus::Confirmed, OrderStatus::OutForDelivery, true],
    'retry a missed delivery' => [OrderStatus::DeliveryFailed, OrderStatus::OutForDelivery, true],
    'return a delivered order' => [OrderStatus::Delivered, OrderStatus::Returned, true],
    'deliver a new order' => [OrderStatus::PendingConfirmation, OrderStatus::Delivered, false],
    'cancel once out for delivery' => [OrderStatus::OutForDelivery, OrderStatus::Cancelled, false],
    'reopen a cancelled order' => [OrderStatus::Cancelled, OrderStatus::Confirmed, false],
    'go back a step' => [OrderStatus::Packed, OrderStatus::Confirmed, false],
]);

it('ends cancelled and returned orders', function (OrderStatus $status) {
    expect($status->nextStatuses())->toBe([]);
    expect($status->restocks())->toBeTrue();
})->with([OrderStatus::Cancelled, OrderStatus::Returned]);

it('allows item changes only until the order leaves', function () {
    $editable = array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => $status->allowsItemChanges());

    expect(array_values($editable))->toBe([OrderStatus::PendingConfirmation, OrderStatus::Confirmed, OrderStatus::Packed]);
});
