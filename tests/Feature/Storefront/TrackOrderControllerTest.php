<?php

use App\Models\Order;
use App\Models\OrderItem;

/**
 * Create an order placed with +974 5555 1234.
 */
function trackedOrder(): Order
{
    return Order::factory()
        ->has(OrderItem::factory(), 'items')
        ->create(['customer_name' => 'Fatima Al-Kuwari', 'customer_phone' => '+97455551234']);
}

it('shows the tracking form', function () {
    $this->get(route('track.show', ['locale' => 'ar']))
        ->assertOk()
        ->assertSee('تتبّع طلبك');
});

it('opens the order when the number and mobile number match', function (Closure $typedNumber, string $typedPhone) {
    $order = trackedOrder();

    $response = $this->post(route('track.lookup', ['locale' => 'en']), ['number' => $typedNumber($order), 'phone' => $typedPhone]);

    expect($response->headers->get('Location'))->toContain("/en/orders/{$order->number}/thank-you?expires=");
    $this->get($response->headers->get('Location'))->assertOk()->assertSee('Fatima Al-Kuwari');
})->with([
    'as printed' => [fn (Order $order): string => $order->number, '5555 1234'],
    'in lower case without the dash' => [fn (Order $order): string => strtolower(str_replace('-', ' ', $order->number)), '+974 5555 1234'],
    'digits only, in Arabic' => [fn (Order $order): string => strtr(preg_replace('/\D/', '', $order->number), ['0' => '٠', '1' => '١']), '٥٥٥٥١٢٣٤'],
]);

it('opens the order in the language of the tracking page', function () {
    $order = trackedOrder();

    $response = $this->post(route('track.lookup', ['locale' => 'ar']), ['number' => $order->number, 'phone' => '5555 1234']);

    expect($response->headers->get('Location'))->toContain("/ar/orders/{$order->number}/thank-you");
});

it('reveals nothing when the mobile number does not match', function () {
    $order = trackedOrder();

    $this->from(route('track.show', ['locale' => 'en']))
        ->post(route('track.lookup', ['locale' => 'en']), ['number' => $order->number, 'phone' => '6666 1234'])
        ->assertRedirect(route('track.show', ['locale' => 'en']))
        ->assertSessionHasErrors(['number' => 'We could not find an order with this number and mobile number.']);
});

it('slows down guessing', function () {
    $order = trackedOrder();

    foreach (range(1, 10) as $attempt) {
        $this->post(route('track.lookup', ['locale' => 'en']), ['number' => $order->number, 'phone' => '6666 1234']);
    }

    $this->post(route('track.lookup', ['locale' => 'en']), ['number' => $order->number, 'phone' => '5555 1234'])
        ->assertTooManyRequests();
});
