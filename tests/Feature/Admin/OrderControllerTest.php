<?php

use App\Actions\UpdateOrderStatus;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShortLink;
use App\Models\User;
use App\Support\Attribution;
use App\Support\QatarPhone;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

/**
 * Place a website order for one piece of a new product.
 *
 * @param  array<string, mixed>  $details  named CheckoutDetails arguments
 */
function websiteOrder(array $details = [], int $stock = 3): Order
{
    $variant = listedProduct(['name_en' => 'Classic crepe jelabiya', 'price' => 18500], ['navy' => ['56' => $stock]])->variants()->sole();

    return placeOrder([$variant->id => 1], checkoutDetails($details));
}

/**
 * Build a complete "enter an order" form submission for two pieces of the given color and size.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function manualOrderPayload(DeliveryZone $zone, ProductVariant $variant, array $overrides = []): array
{
    return [
        'channel' => 'whatsapp',
        'locale' => 'ar',
        'name' => 'Noora Al-Thani',
        'phone' => '6666 1234',
        'email' => '',
        'delivery_zone_id' => $zone->id,
        'zone_number' => '٤٥',
        'street_number' => '330',
        'building_number' => '12',
        'unit' => '',
        'landmark' => '',
        'preferred_delivery_date' => '',
        'delivery_fee' => '',
        'customer_note' => '',
        'admin_note' => 'Asked for a gift box',
        'lines' => [['variant_id' => $variant->id, 'quantity' => 2]],
        ...$overrides,
    ];
}

describe('index', function () {
    it('lists orders with a count for each status', function () {
        websiteOrder(['name' => 'Fatima Al-Kuwari']);
        $confirmed = websiteOrder(['name' => 'Noora Al-Thani', 'phone' => QatarPhone::tryParse('6666 1234')]);
        app(UpdateOrderStatus::class)->handle($confirmed, OrderStatus::Confirmed, null);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSeeInOrder(['Noora Al-Thani', 'Fatima Al-Kuwari'])
            ->assertSee('Waiting for confirmation');

        $this->get(route('admin.orders.index', ['status' => 'confirmed']))
            ->assertSee('Noora Al-Thani')
            ->assertDontSee('Fatima Al-Kuwari');
    });

    it('finds orders by number, name or phone', function (Closure $search) {
        $found = websiteOrder(['name' => 'Fatima Al-Kuwari']);
        websiteOrder(['name' => 'Noora Al-Thani', 'phone' => QatarPhone::tryParse('6666 1234')]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.index', ['search' => $search($found)]))
            ->assertSee('Fatima Al-Kuwari')
            ->assertDontSee('Noora Al-Thani');
    })->with([
        'order number' => fn (Order $order): string => $order->number,
        'name' => fn (Order $order): string => 'Kuwari',
        'phone written locally' => fn (Order $order): string => '5555 1234',
    ]);

    it('downloads the filtered orders as CSV', function () {
        $order = websiteOrder(['name' => 'فاطمة']);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.export', ['status' => 'pending_confirmation']))
            ->assertOk()
            ->assertDownload('orders-'.now()->format('Y-m-d').'.csv');

        expect($response->streamedContent())
            ->toStartWith("\xEF\xBB\xBF")
            ->toContain("{$order->number},")
            ->toContain('فاطمة')
            ->toContain(",'+97455551234,")
            ->toContain(',210');
    });

    it('exports where each order came from', function () {
        websiteOrder(['attribution' => new Attribution(utm: ['utm_source' => 'instagram', 'utm_medium' => 'bio', 'utm_campaign' => 'eid-2026'])]);

        $response = $this->actingAs(User::factory()->staff()->create())->get(route('admin.orders.export'));

        expect($response->streamedContent())
            ->toContain(',Source,Campaign')
            ->toContain(',"instagram / bio",eid-2026');
    });

    it('keeps customers out', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.orders.index'))
            ->assertForbidden();
    });
});

describe('show', function () {
    it('shows what is needed to confirm and deliver the order', function () {
        $order = websiteOrder(['locale' => 'ar', 'landmark' => 'Next to the park']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('Classic crepe jelabiya')
            ->assertSee('Zone 45, Street 330, Building 12')
            ->assertSee('Next to the park')
            ->assertSee('Confirm order')
            ->assertSee('Cancel order')
            ->assertDontSee('Mark delivered')
            ->assertSee('https://wa.me/97455551234?text='.rawurlencode('مرحباً Fatima Al-Kuwari'), false);
    });

    it('shows where the order came from', function () {
        $shortLink = ShortLink::factory()->create(['label' => 'Eid video']);
        $order = websiteOrder([
            'attribution' => new Attribution(
                utm: ['utm_source' => 'tiktok', 'utm_medium' => 'paid', 'utm_campaign' => 'eid-2026'],
                landingUrl: 'http://localhost/ar/products/classic?sl=x',
                shortLinkId: $shortLink->id,
            ),
            'adTrackingAllowed' => true,
        ]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Came from')
            ->assertSeeInOrder(['Source', 'tiktok / paid', 'Campaign', 'eid-2026', 'Short link', 'Eid video', 'First page', '/ar/products/classic', 'Ad tracking', 'Allowed']);
    });

    it('leaves the source out when nothing is known', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.show', websiteOrder()))
            ->assertOk()
            ->assertDontSee('Came from');
    });
});

describe('status', function () {
    it('moves the order on and says so', function () {
        $order = websiteOrder();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.orders.status.update', $order), ['status' => 'confirmed', 'note' => 'Called the customer'])
            ->assertRedirectToRoute('admin.orders.show', $order)
            ->assertSessionHas('status', "Order {$order->number} is now “Confirmed”.");

        expect($order->refresh()->status)->toBe(OrderStatus::Confirmed);
    });

    it('needs a reason to cancel', function () {
        $order = websiteOrder();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.orders.status.update', $order), ['status' => 'cancelled', 'note' => ''])
            ->assertSessionHasErrors(['note' => 'Give a reason for cancelling. It is kept in the order history.']);

        expect($order->refresh()->status)->toBe(OrderStatus::PendingConfirmation);
    });

    it('explains a status change the order cannot make', function () {
        $order = websiteOrder();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.orders.status.update', $order), ['status' => 'delivered'])
            ->assertRedirectToRoute('admin.orders.show', $order)
            ->assertSessionHas('error');

        expect($order->refresh()->status)->toBe(OrderStatus::PendingConfirmation);
    });

    it('marks delivered and cash collected together', function () {
        $order = websiteOrder();
        app(UpdateOrderStatus::class)->handle($order, OrderStatus::Confirmed, null);
        app(UpdateOrderStatus::class)->handle($order, OrderStatus::OutForDelivery, null);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.orders.status.update', $order), ['status' => 'delivered', 'cash_collected' => '1'])
            ->assertRedirectToRoute('admin.orders.show', $order);

        expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
    });
});

it('marks the cash collected', function () {
    $order = websiteOrder();
    app(UpdateOrderStatus::class)->handle($order, OrderStatus::Confirmed, null);
    app(UpdateOrderStatus::class)->handle($order, OrderStatus::OutForDelivery, null);

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.orders.payment.update', $order))
        ->assertSessionHas('status', "Cash for order {$order->number} is marked as collected.");

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
});

it('saves the team note', function () {
    $order = websiteOrder();

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.orders.note.update', $order), ['admin_note' => 'Gift wrap please'])
        ->assertRedirectToRoute('admin.orders.show', $order);

    expect($order->refresh()->admin_note)->toBe('Gift wrap please');
});

describe('items', function () {
    it('changes quantities and adds colors and sizes', function () {
        $order = websiteOrder(stock: 5);
        $added = listedProduct(['price' => 9000], ['olive' => ['58' => 4]])->variants()->sole();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.orders.items.update', $order), [
                'quantities' => [$order->items()->sole()->id => 2],
                'additions' => [['variant_id' => $added->id, 'quantity' => 1], ['variant_id' => $added->id, 'quantity' => 1]],
                'delivery_fee' => '15',
            ])
            ->assertRedirectToRoute('admin.orders.show', $order);

        expect($order->refresh()->grand_total)->toBe(18500 * 2 + 9000 * 2 + 1500);
        expect($added->refresh()->stock)->toBe(2);
    });

    it('shows the problem and keeps the order when stock is short', function () {
        $order = websiteOrder(stock: 2);

        $this->actingAs(User::factory()->staff()->create())
            ->from(route('admin.orders.items.edit', $order))
            ->put(route('admin.orders.items.update', $order), [
                'quantities' => [$order->items()->sole()->id => 5],
                'delivery_fee' => '25',
            ])
            ->assertRedirect(route('admin.orders.items.edit', $order))
            ->assertSessionHas('error');

        expect($order->refresh()->items()->sole()->quantity)->toBe(1);
    });

    it('is closed once the order left with the driver', function () {
        $order = websiteOrder();
        app(UpdateOrderStatus::class)->handle($order, OrderStatus::Confirmed, null);
        app(UpdateOrderStatus::class)->handle($order, OrderStatus::OutForDelivery, null);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.items.edit', $order))
            ->assertRedirectToRoute('admin.orders.show', $order);
    });
});

describe('documents', function () {
    it('prints the invoice in the customer’s language', function () {
        $order = websiteOrder(['locale' => 'ar']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('فاتورة')
            ->assertSee('المبلغ المستحق نقداً عند الاستلام: 210 ر.ق');
    });

    it('prints the packing slip with the cash to collect', function () {
        $order = websiteOrder();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.packing-slip', $order))
            ->assertOk()
            ->assertSee('Collect in cash')
            ->assertSee('QAR 210')
            ->assertSee($order->items()->sole()->sku);
    });
});

describe('entering an order by hand', function () {
    it('shows the form', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.orders.create'))
            ->assertOk()
            ->assertSee('Find a color and size');
    });

    it('creates a confirmed order and takes the stock', function () {
        $zone = DeliveryZone::factory()->create(['fee' => 2000]);
        $variant = listedProduct(['price' => 18500], ['navy' => ['56' => 5]])->variants()->sole();

        $response = $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.orders.store'), manualOrderPayload($zone, $variant));

        $order = Order::query()->sole();
        $response->assertRedirectToRoute('admin.orders.show', $order);
        expect([$order->channel, $order->status, $order->locale, $order->zone_number])->toBe([OrderChannel::WhatsApp, OrderStatus::Confirmed, 'ar', '45']);
        expect([$order->grand_total, $order->admin_note])->toBe([39000, 'Asked for a gift box']);
        expect($variant->refresh()->stock)->toBe(3);
    });

    it('explains when there is not enough stock', function () {
        $zone = DeliveryZone::factory()->create();
        $variant = listedProduct(stockByColor: ['navy' => ['56' => 1]])->variants()->sole();

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.orders.store'), manualOrderPayload($zone, $variant))
            ->assertSessionHasErrors('lines');

        $this->assertDatabaseCount('orders', 0);
    });

    it('needs at least one item', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.orders.store'), manualOrderPayload(DeliveryZone::factory()->create(), listedProduct()->variants()->sole(), ['lines' => []]))
            ->assertSessionHasErrors(['lines' => 'Add at least one item.']);
    });
});

it('finds colors and sizes on sale for the order picker', function () {
    $onSale = listedProduct(['name_en' => 'Classic crepe jelabiya'], ['navy' => ['56' => 4]])->variants()->sole();
    listedProduct(['name_en' => 'Classic draft', 'status' => 'draft']);

    $this->actingAs(User::factory()->staff()->create())
        ->getJson(route('admin.variants.search', ['q' => 'Classic']))
        ->assertOk()
        ->assertJsonCount(1, 'variants')
        ->assertJsonPath('variants.0.id', $onSale->id)
        ->assertJsonPath('variants.0.stock', 4)
        ->assertJsonPath('variants.0.label', 'Classic crepe jelabiya · Navy · 56');
});

it('shows the orders waiting for confirmation in the menu and on the dashboard', function () {
    websiteOrder();
    websiteOrder(['phone' => QatarPhone::tryParse('6666 1234')]);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('(2 waiting for confirmation)')
        ->assertSee('Waiting for confirmation');
});

it('links stock history to the order that changed it', function () {
    $order = websiteOrder();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.stock-movements.index'))
        ->assertSee('Order placed')
        ->assertSee(route('admin.orders.show', $order), false);
});
