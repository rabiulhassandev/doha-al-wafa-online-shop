<?php

use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\User;
use App\Support\CheckoutDetails;
use App\Support\QatarPhone;

it('lists customers with their orders and what they spent, leaving out cancelled orders', function () {
    $variant = listedProduct(['price' => 10000], ['navy' => ['56' => 9]])->variants()->sole();
    $details = fn (): CheckoutDetails => checkoutDetails(['name' => 'Fatima Al-Kuwari']);
    placeOrder([$variant->id => 1], $details());
    $cancelled = placeOrder([$variant->id => 1], $details());
    app(UpdateOrderStatus::class)->handle($cancelled, OrderStatus::Cancelled, null, 'Duplicate');

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertSeeInOrder(['Fatima Al-Kuwari', '+974 5555 1234', '2', 'QAR 125']);
});

it('finds customers by phone number', function () {
    Customer::factory()->create(['name' => 'Fatima Al-Kuwari', 'phone' => '+97455551234']);
    Customer::factory()->create(['name' => 'Noora Al-Thani', 'phone' => '+97466661234']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.customers.index', ['search' => '5555']))
        ->assertSee('Fatima Al-Kuwari')
        ->assertDontSee('Noora Al-Thani');
});

it('shows a customer with their orders', function () {
    $order = placeOrder([listedProduct()->variants()->sole()->id => 1]);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.customers.show', $order->customer))
        ->assertOk()
        ->assertSee($order->number);
});

it('blocks a phone number from ordering on the website', function () {
    $customer = Customer::factory()->create(['name' => 'Fake Orders', 'phone' => '+97455551234']);

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.customers.update', $customer), ['name' => 'Fake Orders', 'email' => '', 'notes' => 'Refused 3 deliveries', 'is_blocked' => '1'])
        ->assertRedirectToRoute('admin.customers.show', $customer)
        ->assertSessionHas('status', 'Fake Orders can no longer order on the website.');

    expect($customer->refresh()->is_blocked)->toBeTrue();
    expect($customer->notes)->toBe('Refused 3 deliveries');
    expect(Customer::isBlocked(QatarPhone::tryParse('5555 1234')))->toBeTrue();
});
