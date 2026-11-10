<?php

use App\Models\Address;
use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(fn () => Mail::fake());

/**
 * Put one QAR 185 piece in a new bag.
 */
function accountBag(): CartItem
{
    return CartItem::factory()->for(listedProduct(['price' => 18500])->variants()->sole())->create(['quantity' => 1]);
}

/**
 * Build a complete checkout form submission for the given area.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function accountCheckoutPayload(DeliveryZone $zone, array $overrides = []): array
{
    return [
        'name' => 'Fatima Al-Kuwari',
        'phone' => '5555 1234',
        'delivery_zone_id' => $zone->id,
        'zone_number' => '45',
        'street_number' => '330',
        'building_number' => '12',
        'payment_method' => 'cash_on_delivery',
        'expected_subtotal' => 18500,
        'checkout_token' => (string) Str::uuid(),
        ...$overrides,
    ];
}

it('fills in the shopper’s details and first saved address', function () {
    $user = User::factory()->create(['name' => 'Fatima Al-Kuwari', 'email' => 'fatima@example.com', 'phone' => '+97455551234']);
    $zone = DeliveryZone::factory()->create(['name_en' => 'Al Wakrah']);
    Address::factory()->for($user)->for($zone)->default()->create(['zone_number' => '45', 'street_number' => '330', 'building_number' => '12', 'landmark' => 'Next to the park']);
    Address::factory()->for($user)->create(['label' => 'Work']);
    $item = accountBag();

    $this->actingAs($user)
        ->withCookie(CartService::COOKIE, $item->cart->token)
        ->get(route('checkout.show', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('value="Fatima Al-Kuwari"', false)
        ->assertSee('value="+974 5555 1234"', false)
        ->assertSee('value="fatima@example.com"', false)
        ->assertSee('value="330"', false)
        ->assertSee('value="Next to the park"', false)
        ->assertSee('Your saved addresses')
        ->assertSee('<option value="'.$zone->id.'" selected', false)
        ->assertDontSee('Sign in for faster checkout');
});

it('invites guests to sign in, without making them', function () {
    $item = accountBag();

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->get(route('checkout.show', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Sign in for faster checkout')
        ->assertSee(route('account.login', ['locale' => 'en', 'next' => 'checkout']), false)
        ->assertDontSee('Save this address to my account');
});

it('links the order to the account and saves the address once', function () {
    $user = User::factory()->create();
    $zone = DeliveryZone::factory()->create();

    foreach (range(1, 2) as $attempt) {
        $item = accountBag();
        $this->actingAs($user)
            ->withCookie(CartService::COOKIE, $item->cart->token)
            ->post(route('checkout.store', ['locale' => 'en']), accountCheckoutPayload($zone, ['save_address' => '1']))
            ->assertSessionHasNoErrors();
    }

    expect(Order::query()->where('user_id', $user->id)->count())->toBe(2);
    $address = $user->addresses()->sole();
    expect([$address->zone_number, $address->street_number, $address->is_default])->toBe(['45', '330', true]);
});

it('leaves the account alone when the shopper does not ask to save the address', function () {
    $user = User::factory()->create();
    $item = accountBag();

    $this->actingAs($user)
        ->withCookie(CartService::COOKIE, $item->cart->token)
        ->post(route('checkout.store', ['locale' => 'en']), accountCheckoutPayload(DeliveryZone::factory()->create()));

    expect($user->addresses()->count())->toBe(0);
    expect(Order::query()->sole()->user_id)->toBe($user->id);
});

it('keeps guest orders off any account', function () {
    $item = accountBag();

    $this->withCookie(CartService::COOKIE, $item->cart->token)
        ->post(route('checkout.store', ['locale' => 'en']), accountCheckoutPayload(DeliveryZone::factory()->create()))
        ->assertSessionHasNoErrors();

    expect(Order::query()->sole()->user_id)->toBeNull();
});
