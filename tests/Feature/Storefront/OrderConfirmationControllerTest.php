<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Settings;

/**
 * Create a placed order with one line.
 *
 * @param  array<string, mixed>  $attributes
 */
function placedOrder(array $attributes = []): Order
{
    return Order::factory()
        ->has(OrderItem::factory()->state(['product_name_en' => 'Classic crepe jelabiya', 'product_name_ar' => 'جلابية كريب كلاسيكية']), 'items')
        ->create(['customer_name' => 'Fatima Al-Kuwari', 'customer_phone' => '+97455551234', 'grand_total' => 20500, ...$attributes]);
}

it('shows the order on its signed link', function () {
    app(Settings::class)->set(['whatsapp_number' => '+97433334444']);
    $order = placedOrder();

    $this->get($order->confirmationUrl())
        ->assertOk()
        ->assertSee('Thank you, Fatima Al-Kuwari!')
        ->assertSee($order->number)
        ->assertSee('Classic crepe jelabiya')
        ->assertSee('You pay QAR 205 in cash when your order arrives.')
        ->assertSee('Zone 45, Street 330, Building 12')
        ->assertSee('https://wa.me/97433334444?text=', false);
});

it('shows the order in the language it was placed in', function () {
    $order = placedOrder(['locale' => 'ar']);

    $this->get($order->confirmationUrl())
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('جلابية كريب كلاسيكية')
        ->assertSee('تدفع 205 ر.ق نقداً عند وصول طلبك.');
});

it('refuses links that are missing or altered', function (Closure $url) {
    $order = placedOrder();
    placedOrder();

    $this->get($url($order))
        ->assertForbidden()
        ->assertSee('This link has expired')
        ->assertDontSee('Fatima Al-Kuwari');
})->with([
    'unsigned' => fn (Order $order): string => route('orders.thank-you', ['locale' => 'en', 'order' => $order]),
    'another order' => fn (Order $order): string => str_replace($order->number, Order::numberFor($order->id + 1), $order->confirmationUrl()),
]);

it('stops working after 30 days', function () {
    $order = placedOrder();
    $url = $order->confirmationUrl();

    $this->travel(31)->days();

    $this->get($url)->assertForbidden();
});

it('keeps the link working when switching language', function () {
    $order = placedOrder();

    $page = $this->get($order->confirmationUrl())->assertOk()->getContent();
    preg_match('/hreflang="ar" href="([^"]+)"/', $page, $matches);
    $arabicUrl = html_entity_decode($matches[1] ?? '');

    expect($arabicUrl)->toContain("/ar/orders/{$order->number}/thank-you");
    $this->get($arabicUrl)->assertOk()->assertSee('dir="rtl"', false);
});
