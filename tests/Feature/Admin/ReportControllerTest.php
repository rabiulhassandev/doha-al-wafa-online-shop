<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00'));
});

/**
 * Create a kept order placed on a day, with one piece.
 *
 * @param  array<string, mixed>  $attributes
 */
function orderPlacedOn(string $placedAt, int $grandTotal = 20500, array $attributes = []): Order
{
    $order = Order::factory()->create(['placed_at' => CarbonImmutable::parse($placedAt), 'grand_total' => $grandTotal, ...$attributes]);
    OrderItem::factory()->for($order)->create(['product_name_en' => 'Classic crepe jelabiya', 'color_name_en' => 'Navy', 'size_name_en' => '56']);

    return $order;
}

describe('page', function () {
    it('shows the figures for the last 30 days by default', function () {
        orderPlacedOn('2026-11-02 10:00', 20500, ['utm_source' => 'instagram', 'utm_medium' => 'bio']);
        orderPlacedOn('2026-11-09 10:00', 40000);
        orderPlacedOn('2026-10-01 10:00', 99900);

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Last 30 days (12 Oct – 10 Nov 2026)')
            ->assertSee('QAR 605')
            ->assertSee('QAR 302.50')
            ->assertSee('instagram / bio')
            ->assertSee('Classic crepe jelabiya')
            ->assertSeeInOrder(['Sales by day', 'Mon 2 Nov: QAR 205, 1 order'], false)
            ->assertDontSee('QAR 999');
    });

    it('compares with the days before', function () {
        orderPlacedOn('2026-11-09 10:00', 30000);
        orderPlacedOn('2026-11-02 10:00', 20000);

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.reports.index', ['range' => '7d']))
            ->assertOk()
            ->assertSee('+50% vs the 7 days before');
    });

    it('shows chosen dates', function () {
        orderPlacedOn('2026-10-15 10:00', 12300);

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.reports.index', ['range' => 'custom', 'from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('1 Oct – 31 Oct 2026')
            ->assertSee('QAR 123');
    });

    it('explains empty dates instead of drawing an empty chart', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.reports.index', ['range' => 'today']))
            ->assertOk()
            ->assertSee('No sales in these dates.')
            ->assertSee('No orders placed in these dates.');
    });

    it('is for the owner only', function (User $user) {
        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    })->with([
        'staff' => fn () => User::factory()->staff()->create(),
        'customer' => fn () => User::factory()->create(),
    ]);
});

describe('CSV', function () {
    it('downloads a report for the same dates', function () {
        orderPlacedOn('2026-11-02 10:00', 20500, ['utm_source' => 'instagram', 'utm_medium' => 'bio']);
        orderPlacedOn('2026-11-03 10:00', 40000, ['status' => OrderStatus::Cancelled, 'utm_source' => 'tiktok']);

        $response = $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.reports.export', ['report' => 'sources', 'range' => '7d']))
            ->assertOk()
            ->assertDownload('report-sources-2026-11-04-to-2026-11-10.csv');

        expect($response->streamedContent())->toBe("\xEF\xBB\xBF".'Source,Orders,"Sales (QAR)"'."\n");

        $response = $this->get(route('admin.reports.export', ['report' => 'sources', 'range' => '30d']));

        expect($response->streamedContent())->toBe("\xEF\xBB\xBF".'Source,Orders,"Sales (QAR)"'."\n".'"instagram / bio",1,205'."\n");
    });

    it('lists the best sellers with the stock left', function () {
        orderPlacedOn('2026-11-02 10:00');

        $response = $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.reports.export', ['report' => 'best-sellers']));

        expect($response->streamedContent())->toContain('Product,Color,Size,SKU,Pieces,"Item sales (QAR)","In stock now"')
            ->toContain('"Classic crepe jelabiya",Navy,56,');
    });

    it('has a file for every report on the page', function (string $report) {
        orderPlacedOn('2026-11-02 10:00');

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.reports.export', ['report' => $report]))
            ->assertOk();
    })->with(['sales', 'statuses', 'sources', 'areas', 'campaigns', 'coupons', 'products', 'colors', 'sizes', 'best-sellers']);

    it('has no other reports', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->get('/admin/reports/customers.csv')
            ->assertNotFound();
    });

    it('is for the owner only', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.reports.export', ['report' => 'sales']))
            ->assertForbidden();
    });
});
