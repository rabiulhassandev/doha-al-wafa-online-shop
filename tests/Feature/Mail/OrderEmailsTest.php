<?php

use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Models\OrderItem;

/**
 * Create a placed order with one line, as the emails expect.
 *
 * @param  array<string, mixed>  $attributes
 */
function emailedOrder(array $attributes = []): Order
{
    return Order::factory()
        ->has(OrderItem::factory()->state(['sku' => 'JLB012-NVY-56', 'product_name_ar' => 'جلابية كريب كلاسيكية']), 'items')
        ->create(['customer_name' => 'Fatima Al-Kuwari', 'customer_phone' => '+97455551234', 'grand_total' => 20500, ...$attributes]);
}

it('emails the customer in Arabic, right to left, with a link to the order', function () {
    $order = emailedOrder(['locale' => 'ar']);

    $mail = (new OrderPlaced($order))->locale('ar');

    $mail->assertHasSubject("تم استلام طلبك {$order->number}");
    $mail->assertSeeInHtml('dir="rtl"', false);
    $mail->assertSeeInHtml('جلابية كريب كلاسيكية');
    $mail->assertSeeInHtml('تدفع 205 ر.ق نقداً عند وصول طلبك.');
    $mail->assertSeeInHtml("/ar/orders/{$order->number}/thank-you?expires=", false);
});

it('gives the store team what they need to confirm and deliver', function () {
    $order = emailedOrder([
        'latitude' => '25.2854000',
        'longitude' => '51.5310000',
        'customer_note' => 'Please call before arriving.',
    ]);

    $mail = new NewOrderAlert($order);

    $mail->assertHasSubject("New order {$order->number} · QAR 205 · Doha");
    $mail->assertSeeInHtml('Collect QAR 205 in cash on delivery.');
    $mail->assertSeeInHtml('https://wa.me/97455551234', false);
    $mail->assertSeeInHtml('SKU JLB012-NVY-56');
    $mail->assertSeeInHtml('Please call before arriving.');
    $mail->assertSeeInHtml('query=25.2854000,51.5310000', false);
});
