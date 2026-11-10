<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Settings;
use Carbon\CarbonImmutable;

it('redirects guests to the admin sign-in page', function () {
    $this->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login');
});

it('forbids customers', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('shows the dashboard to team members', function (User $member) {
    $this->actingAs($member)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee($member->name);
})->with([
    'owner' => fn () => User::factory()->owner()->create(),
    'staff' => fn () => User::factory()->staff()->create(),
]);

it('marks setup tasks as done once their settings are saved', function () {
    app(Settings::class)->set([
        'whatsapp_number' => '+97455551234',
        'tiktok_url' => 'https://www.tiktok.com/@dohaalwafa',
    ]);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('setupTasks', [
            ['label' => 'Add the store WhatsApp number', 'done' => true],
            ['label' => 'Add a contact phone number', 'done' => false],
            ['label' => 'Add a contact email', 'done' => false],
            ['label' => 'Link at least one social media account', 'done' => true],
        ]);
});

describe('figures', function () {
    beforeEach(function () {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00'));

        $order = Order::factory()->create(['placed_at' => now()->subDay(), 'grand_total' => 41000, 'utm_source' => 'tiktok', 'utm_medium' => 'paid']);
        OrderItem::factory()->for($order)->create(['product_name_en' => 'Embroidered kaftan', 'color_name_en' => 'Emerald', 'quantity' => 2, 'unit_price' => 19500]);
        Order::factory()->create(['placed_at' => now(), 'grand_total' => 20500]);
    });

    it('shows the owner sales, best sellers and where orders came from', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Orders today', '1 · QAR 205'])
            ->assertSeeInOrder(['Sales this month', 'QAR 615'])
            ->assertSee('Mon 9 Nov: QAR 410, 1 order')
            ->assertSeeInOrder(['Best sellers, last 30 days', 'Embroidered kaftan', '2', 'Top colors', 'Emerald'])
            ->assertSee('tiktok / paid');
    });

    it('shows staff the work and the best sellers, without sales', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Embroidered kaftan')
            ->assertDontSee('Sales this month')
            ->assertDontSee('QAR 205')
            ->assertDontSee('tiktok / paid')
            ->assertDontSee('Open reports');
    });
});

it('lists the sizes running low, as the inventory filter does', function () {
    app(Settings::class)->set(['low_stock_threshold' => 3]);
    $low = ProductVariant::factory()->inStock(2)->create();
    ProductVariant::factory()->inStock(0)->create();
    ProductVariant::factory()->inStock(9)->create();
    ProductVariant::factory()->inStock(1)->notOffered()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Sizes running low', '1'])
        ->assertSee(route('admin.inventory.adjust', $low))
        ->assertSee('2 left');
});
