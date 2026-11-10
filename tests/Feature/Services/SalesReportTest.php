<?php

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Services\SalesReport;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;

/**
 * Create an order with its pieces, each [product, color, size, quantity, unit price].
 *
 * @param  array<string, mixed>  $attributes
 * @param  list<array{0: string, 1: string, 2: string, 3: int, 4: int, 5?: int|null}>  $items  the 6th value links a product variant
 */
function reportOrder(string $placedAt, OrderStatus $status, int $grandTotal, array $items, array $attributes = []): Order
{
    $order = Order::factory()->create([
        'placed_at' => CarbonImmutable::parse($placedAt),
        'status' => $status,
        'grand_total' => $grandTotal,
        ...$attributes,
    ]);

    foreach ($items as $item) {
        OrderItem::factory()->for($order)->create([
            'product_name_en' => $item[0],
            'color_name_en' => $item[1],
            'color_hex' => $item[1] === 'Navy' ? '#1f2a44' : '#5b6b3a',
            'size_name_en' => $item[2],
            'quantity' => $item[3],
            'unit_price' => $item[4],
            'product_variant_id' => $item[5] ?? null,
        ]);
    }

    return $order;
}

function collectCash(Order $order, string $paidAt): void
{
    Payment::factory()->for($order)->create(['status' => PaymentStatus::Paid, 'amount' => $order->grand_total, 'paid_at' => CarbonImmutable::parse($paidAt)]);
}

/**
 * The first ten days of November 2026, as the owner would pick them.
 */
function novemberReport(): ReportPeriod
{
    return ReportPeriod::make(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-10'), 'custom');
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00'));

    $this->variant = ProductVariant::factory()->inStock(1)->create();

    // Delivered, from an Instagram bio link with the Eid campaign; cash collected on 3 Nov.
    $delivered = reportOrder('2026-11-02 10:00', OrderStatus::Delivered, 20500, [['Classic crepe jelabiya', 'Navy', '56', 1, 18500]], [
        'utm_source' => 'instagram', 'utm_medium' => 'bio', 'utm_campaign' => 'eid-2026', 'delivery_zone_name_en' => 'Doha',
    ]);
    collectCash($delivered, '2026-11-03 18:00');

    // Waiting, from a TikTok ad, two pieces with a discount code.
    reportOrder('2026-11-02 15:00', OrderStatus::PendingConfirmation, 40000, [
        ['Classic crepe jelabiya', 'Olive', '58', 1, 18500],
        ['Embroidered kaftan', 'Navy', '56', 1, 23500],
    ], ['click_id_type' => 'tiktok', 'click_id' => 'E.C.P.1', 'coupon_code' => 'EID10', 'discount_total' => 4000, 'delivery_zone_name_en' => 'Lusail']);

    // Cancelled, from the same Instagram campaign: an order of nothing.
    reportOrder('2026-11-05 11:00', OrderStatus::Cancelled, 30000, [['Embroidered kaftan', 'Navy', '56', 1, 23500]], [
        'utm_source' => 'instagram', 'utm_medium' => 'bio', 'utm_campaign' => 'eid-2026', 'delivery_zone_name_en' => 'Doha',
    ]);

    // Returned after the cash was collected, found on Google.
    $returned = reportOrder('2026-11-06 09:00', OrderStatus::Returned, 20500, [['Classic crepe jelabiya', 'Navy', '56', 1, 18500]], [
        'referrer' => 'https://www.google.com/search?q=jelabiya', 'delivery_zone_name_en' => 'Doha',
    ]);
    collectCash($returned, '2026-11-07 12:00');

    // Delivered, entered by the team from WhatsApp; two pieces of one color and size.
    $whatsApp = reportOrder('2026-11-08 20:00', OrderStatus::Delivered, 40000, [['Classic crepe jelabiya', 'Navy', '54', 2, 19000, $this->variant->id]], [
        'channel' => OrderChannel::WhatsApp, 'delivery_zone_name_en' => 'Al Wakra',
    ]);
    collectCash($whatsApp, '2026-11-09 17:00');

    // Confirmed today, nothing known about where it came from.
    reportOrder('2026-11-10 09:00', OrderStatus::Confirmed, 20500, [['Classic crepe jelabiya', 'Navy', '56', 1, 18500]], ['delivery_zone_name_en' => 'Doha']);

    // Placed the night before the period, but its cash was collected on 1 Nov.
    $lastMonth = reportOrder('2026-10-31 23:30', OrderStatus::Delivered, 99900, [['Classic crepe jelabiya', 'Navy', '56', 5, 18500]]);
    collectCash($lastMonth, '2026-11-01 10:00');

    // Placed just after the period.
    reportOrder('2026-11-11 00:00', OrderStatus::Confirmed, 50000, [['Classic crepe jelabiya', 'Navy', '56', 2, 18500]]);
});

it('adds up the headline figures', function () {
    $summary = app(SalesReport::class)->summary(novemberReport());

    expect($summary)->toMatchArray([
        'placed' => 6,
        'orders' => 5,
        'sales' => 121000,
        'salesOrders' => 4,
        'averageOrder' => 30250,
        'pieces' => 6,
        'discounts' => 4000,
        'cashCollected' => 20500 + 40000 + 99900,
        'cancelled' => 1,
        'delivered' => 2,
        'returned' => 1,
    ]);
    expect($summary['cancellationRate'])->toEqualWithDelta(1 / 6, 0.0001);
    expect($summary['returnRate'])->toEqualWithDelta(1 / 3, 0.0001);
});

it('gives zero figures for dates without orders', function () {
    $summary = app(SalesReport::class)->summary(ReportPeriod::make(CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-09-30'), 'custom'));

    expect([$summary['orders'], $summary['sales'], $summary['averageOrder'], $summary['cancellationRate'], $summary['returnRate']])->toBe([0, 0, 0, null, null]);
});

it('spreads sales over the days of the period', function () {
    $days = collect(app(SalesReport::class)->salesOverTime(novemberReport()))
        ->mapWithKeys(fn (array $day): array => [$day['start']->toDateString() => [$day['orders'], $day['sales']]]);

    expect($days)->toHaveCount(10);
    expect($days->filter(fn (array $day): bool => $day !== [0, 0])->all())->toBe([
        '2026-11-02' => [2, 60500],
        '2026-11-06' => [1, 0],
        '2026-11-08' => [1, 40000],
        '2026-11-10' => [1, 20500],
    ]);
});

it('groups sales by week from Sunday', function () {
    $period = ReportPeriod::make(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-10'), 'custom', 'week');

    $weeks = array_map(fn (array $week): array => [$week['label'], $week['orders'], $week['sales']], app(SalesReport::class)->salesOverTime($period));

    expect($weeks)->toBe([
        ['Week of 1 Nov', 3, 60500],
        ['Week of 8 Nov', 2, 60500],
    ]);
});

it('counts every order placed by its status now', function () {
    $statuses = array_map(fn (array $row): array => [$row['status'], $row['orders'], $row['value']], app(SalesReport::class)->byStatus(novemberReport()));

    expect($statuses)->toBe([
        [OrderStatus::PendingConfirmation, 1, 40000],
        [OrderStatus::Confirmed, 1, 20500],
        [OrderStatus::Delivered, 2, 60500],
        [OrderStatus::Cancelled, 1, 30000],
        [OrderStatus::Returned, 1, 20500],
    ]);
});

it('names where orders came from, the team’s own channels included', function () {
    expect(app(SalesReport::class)->bySource(novemberReport()))->toBe([
        ['label' => 'TikTok ad', 'orders' => 1, 'sales' => 40000],
        ['label' => 'WhatsApp (entered by the team)', 'orders' => 1, 'sales' => 40000],
        ['label' => 'Direct or unknown', 'orders' => 1, 'sales' => 20500],
        ['label' => 'instagram / bio', 'orders' => 1, 'sales' => 20500],
        ['label' => 'www.google.com', 'orders' => 1, 'sales' => 0],
    ]);
});

it('breaks orders down by area, campaign and discount code', function () {
    $report = app(SalesReport::class);

    expect($report->byArea(novemberReport()))->toBe([
        ['label' => 'Doha', 'orders' => 3, 'sales' => 41000],
        ['label' => 'Al Wakra', 'orders' => 1, 'sales' => 40000],
        ['label' => 'Lusail', 'orders' => 1, 'sales' => 40000],
    ]);
    expect($report->byCampaign(novemberReport()))->toBe([
        ['label' => 'eid-2026', 'orders' => 1, 'sales' => 20500],
    ]);
    expect($report->byCoupon(novemberReport()))->toBe([
        ['label' => 'EID10', 'orders' => 1, 'sales' => 40000, 'discount' => 4000],
    ]);
});

it('counts the pieces kept by product, color and size', function () {
    $report = app(SalesReport::class);

    expect($report->products(novemberReport()))->toBe([
        ['label' => 'Classic crepe jelabiya', 'orders' => 4, 'pieces' => 5, 'sales' => 93500],
        ['label' => 'Embroidered kaftan', 'orders' => 1, 'pieces' => 1, 'sales' => 23500],
    ]);
    expect(array_map(fn (array $row): array => [$row['label'], $row['hex'], $row['pieces'], $row['sales']], $report->colors(novemberReport())))->toBe([
        ['Navy', '#1f2a44', 5, 98500],
        ['Olive', '#5b6b3a', 1, 18500],
    ]);
    expect(array_map(fn (array $row): array => [$row['label'], $row['pieces']], $report->sizes(novemberReport())))->toBe([
        ['56', 3],
        ['54', 2],
        ['58', 1],
    ]);
});

it('lists the best-selling colors and sizes with the stock left', function () {
    $bestSellers = app(SalesReport::class)->bestSellers(novemberReport(), 3);

    expect(array_map(fn (array $row): array => [$row['label'], $row['pieces'], $row['sales'], $row['stock']], $bestSellers))->toBe([
        ['Classic crepe jelabiya – Navy / 54', 2, 38000, 1],
        ['Classic crepe jelabiya – Navy / 56', 2, 37000, null],
        ['Embroidered kaftan – Navy / 56', 1, 23500, null],
    ]);
});
