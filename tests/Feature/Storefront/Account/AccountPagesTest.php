<?php

use App\Models\Address;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

describe('my orders', function () {
    it('lists only the orders placed from this account', function () {
        $user = User::factory()->create();
        $mine = Order::factory()->create(['user_id' => $user->id]);
        $someoneElses = Order::factory()->create(['user_id' => User::factory()->create()->id]);
        $guestOrder = Order::factory()->create(['user_id' => null, 'customer_phone' => '+97455551234']);

        $this->actingAs($user)
            ->get(route('account.orders', ['locale' => 'en']))
            ->assertOk()
            ->assertSee($mine->number)
            ->assertDontSee($someoneElses->number)
            ->assertDontSee($guestOrder->number);
    });

    it('opens an order from the account and hides other shoppers’ orders', function () {
        $user = User::factory()->create();
        $mine = Order::factory()->has(OrderItem::factory()->state(['product_name_en' => 'Classic crepe jelabiya']), 'items')->create(['user_id' => $user->id]);
        $someoneElses = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($user)
            ->get(route('account.orders.show', ['locale' => 'en', 'order' => $mine]))
            ->assertOk()
            ->assertSee("Order {$mine->number}")
            ->assertSee('Classic crepe jelabiya')
            ->assertDontSee('Thank you,');

        $this->get(route('account.orders.show', ['locale' => 'en', 'order' => $someoneElses]))->assertNotFound();
    });
});

describe('my details', function () {
    it('saves the name, email and mobile number', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.profile.update', ['locale' => 'en']), ['name' => 'Fatima K.', 'email' => 'New@Example.com', 'phone' => '+974 6666 1234'])
            ->assertRedirect(route('account.profile.edit', ['locale' => 'en']));

        $user->refresh();
        expect([$user->name, $user->email, $user->phone])->toBe(['Fatima K.', 'new@example.com', '+97466661234']);
    });

    it('keeps emails unique', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs(User::factory()->create())
            ->put(route('account.profile.update', ['locale' => 'en']), ['name' => 'Fatima', 'email' => 'taken@example.com', 'phone' => '5555 1234'])
            ->assertSessionHasErrors(['email' => 'Another account already uses this email.']);
    });

    it('changes the password only with the current one', function () {
        $user = User::factory()->create(['password' => 'old-secret-1']);

        $this->actingAs($user)
            ->put(route('account.password.update', ['locale' => 'en']), ['current_password' => 'wrong', 'password' => 'new-secret-2', 'password_confirmation' => 'new-secret-2'])
            ->assertSessionHasErrors(['current_password' => 'This is not your current password.'], errorBag: 'password');

        $this->put(route('account.password.update', ['locale' => 'en']), ['current_password' => 'old-secret-1', 'password' => 'new-secret-2', 'password_confirmation' => 'new-secret-2'])
            ->assertSessionHas('account_status', 'Your password was changed.');

        expect(Hash::check('new-secret-2', $user->refresh()->password))->toBeTrue();
    });
});

describe('addresses', function () {
    /**
     * @return array<string, mixed>
     */
    function addressPayload(DeliveryZone $zone, array $overrides = []): array
    {
        return ['label' => 'Home', 'delivery_zone_id' => $zone->id, 'zone_number' => '٤٥', 'street_number' => '330', 'building_number' => '12', 'unit' => '', 'landmark' => '', ...$overrides];
    }

    it('makes the first address the one used first, and lets another take its place', function () {
        $user = User::factory()->create();
        $zone = DeliveryZone::factory()->create();

        $this->actingAs($user)->post(route('account.addresses.store', ['locale' => 'en']), addressPayload($zone))
            ->assertRedirect(route('account.addresses.index', ['locale' => 'en']));
        $first = $user->addresses()->sole();
        expect([$first->is_default, $first->zone_number])->toBe([true, '45']);

        $this->post(route('account.addresses.store', ['locale' => 'en']), addressPayload($zone, ['label' => 'Work', 'is_default' => '1']));

        expect($user->addresses()->where('is_default', true)->sole()->label)->toBe('Work');
        expect($first->refresh()->is_default)->toBeFalse();
    });

    it('hands the default to the oldest address when it is deleted', function () {
        $user = User::factory()->create();
        $oldest = Address::factory()->for($user)->create();
        $default = Address::factory()->for($user)->default()->create();

        $this->actingAs($user)->delete(route('account.addresses.destroy', ['locale' => 'en', 'address' => $default]));

        $this->assertModelMissing($default);
        expect($oldest->refresh()->is_default)->toBeTrue();
    });

    it('keeps other shoppers’ addresses out of reach', function () {
        $address = Address::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('account.addresses.update', ['locale' => 'en', 'address' => $address]), addressPayload(DeliveryZone::factory()->create()))
            ->assertNotFound();

        $this->delete(route('account.addresses.destroy', ['locale' => 'en', 'address' => $address]))->assertNotFound();
        $this->assertModelExists($address);
    });
});

describe('wishlist', function () {
    it('saves the browser’s wishlist on the account, keeping only real products', function () {
        $user = User::factory()->create();
        [$first, $second] = Product::factory()->count(2)->create();

        $this->actingAs($user)
            ->putJson(route('account.wishlist.sync', ['locale' => 'en']), ['ids' => [$second->id, 999999, $first->id, $second->id]])
            ->assertOk()
            ->assertJson(['ids' => [$second->id, $first->id]]);

        expect($user->wishlistProducts()->pluck('products.id')->sort()->values()->all())->toBe(collect([$first->id, $second->id])->sort()->values()->all());

        $this->putJson(route('account.wishlist.sync', ['locale' => 'en']), ['ids' => []])->assertOk();
        expect($user->wishlistProducts()->count())->toBe(0);
    });

    it('hands the saved wishlist to every page for signed-in shoppers', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $user->wishlistProducts()->attach($product);

        $this->actingAs($user)
            ->get(route('home', ['locale' => 'en']))
            ->assertSee('id="wishlist-account"', false)
            ->assertSee('data-ids="['.$product->id.']"', false);

        auth()->logout();
        $this->get(route('home', ['locale' => 'en']))->assertDontSee('id="wishlist-account"', false);
    });

    it('needs an account to save the wishlist', function () {
        $this->putJson(route('account.wishlist.sync', ['locale' => 'en']), ['ids' => [1]])->assertUnauthorized();
    });
});
