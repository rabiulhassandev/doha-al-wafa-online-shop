<?php

use App\Models\Order;
use App\Models\Page;
use App\Models\User;
use App\Services\Settings;

it('shows the exchange promise in the trust badges, in the shopper’s language', function () {
    app(Settings::class)->set(['exchange_promise_en' => 'Exchange within 7 days', 'exchange_promise_ar' => 'استبدال خلال 7 أيام']);

    $this->get(route('home', ['locale' => 'ar']))->assertSee('استبدال سهل')->assertSee('استبدال خلال 7 أيام');
    $this->get(route('home', ['locale' => 'en']))->assertSee('Exchange within 7 days');
});

it('leaves the exchange promise out when it is empty', function () {
    $this->get(route('home', ['locale' => 'en']))->assertDontSee('Easy exchange');
});

it('asks about ad cookies only when the owner turns it on', function () {
    Page::factory()->create(['slug' => 'privacy', 'title_en' => 'Privacy']);

    $this->get(route('home', ['locale' => 'en']))->assertDontSee('x-data="consentBar"', false);

    app(Settings::class)->set(['consent_bar_enabled' => true]);

    $this->get(route('home', ['locale' => 'en']))
        ->assertSee('x-data="consentBar"', false)
        ->assertSee('May we use cookies to measure our ads?')
        ->assertSee(route('pages.show', ['locale' => 'en', 'page' => 'privacy']));
});

it('uses the owner’s own WhatsApp wording on the order page', function () {
    app(Settings::class)->set(['whatsapp_message_confirm_en' => 'Hi :name! Confirm :number for :total?']);
    $order = Order::factory()->create(['customer_name' => 'Fatima', 'customer_phone' => '+97455551234', 'grand_total' => 20500, 'locale' => 'en']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.orders.show', $order))
        ->assertSee('https://wa.me/97455551234?text='.rawurlencode("Hi Fatima! Confirm {$order->number} for QAR 205?"), false)
        ->assertSee('https://wa.me/97455551234?text='.rawurlencode('Hello Fatima, your order'), false);
});
