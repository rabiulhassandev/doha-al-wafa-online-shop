<?php

namespace App\Services;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The sales figures behind the dashboard and the reports, for orders placed in a period (Qatar time).
 *
 * The same rules apply everywhere, matching the discount code and short link lists:
 *  - "orders" leaves out cancelled orders;
 *  - "sales" also leaves out returned orders, so it is the money the store keeps;
 *  - pieces, products, colors and sizes count only the orders in "sales".
 * Amounts are in dirhams.
 */
class SalesReport
{
    /**
     * Statuses whose money the store does not keep.
     */
    private const NOT_KEPT = [OrderStatus::Cancelled->value, OrderStatus::Returned->value];

    /**
     * Get the headline figures.
     *
     * @return array{placed: int, orders: int, sales: int, salesOrders: int, averageOrder: int, pieces: int, discounts: int, cashCollected: int, cancelled: int, delivered: int, returned: int, cancellationRate: float|null, returnRate: float|null}
     */
    public function summary(ReportPeriod $period): array
    {
        $totals = $this->orders($period)
            ->selectRaw('COUNT(*) as placed')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as cancelled', [OrderStatus::Cancelled->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as delivered', [OrderStatus::Delivered->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as returned', [OrderStatus::Returned->value])
            ->selectRaw('COUNT(CASE WHEN status NOT IN (?, ?) THEN 1 END) as sales_orders', self::NOT_KEPT)
            ->selectRaw('COALESCE(SUM(CASE WHEN status NOT IN (?, ?) THEN grand_total END), 0) as sales', self::NOT_KEPT)
            ->selectRaw('COALESCE(SUM(CASE WHEN status NOT IN (?, ?) THEN discount_total END), 0) as discounts', self::NOT_KEPT)
            ->first();

        $placed = (int) $totals->placed;
        $cancelled = (int) $totals->cancelled;
        $delivered = (int) $totals->delivered;
        $returned = (int) $totals->returned;
        $salesOrders = (int) $totals->sales_orders;
        $sales = (int) $totals->sales;

        return [
            'placed' => $placed,
            'orders' => $placed - $cancelled,
            'sales' => $sales,
            'salesOrders' => $salesOrders,
            'averageOrder' => $salesOrders > 0 ? intdiv($sales, $salesOrders) : 0,
            'pieces' => (int) $this->keptItems($period)->sum('order_items.quantity'),
            'discounts' => (int) $totals->discounts,
            'cashCollected' => $this->cashCollected($period),
            'cancelled' => $cancelled,
            'delivered' => $delivered,
            'returned' => $returned,
            'cancellationRate' => $placed > 0 ? $cancelled / $placed : null,
            'returnRate' => $delivered + $returned > 0 ? $returned / ($delivered + $returned) : null,
        ];
    }

    /**
     * Get orders and sales for each bar of the chart: each day, week or month of the period.
     *
     * @return list<array{start: CarbonImmutable, label: string, orders: int, sales: int}>
     */
    public function salesOverTime(ReportPeriod $period): array
    {
        $days = $this->withTotals($this->orders($period))
            ->selectRaw('DATE(placed_at) as day')
            ->groupByRaw('DATE(placed_at)')
            ->get();

        $buckets = [];

        foreach ($period->buckets() as $bucket) {
            $buckets[$bucket->toDateString()] = ['start' => $bucket, 'label' => $period->bucketLabel($bucket), 'orders' => 0, 'sales' => 0];
        }

        foreach ($days as $day) {
            $key = $period->bucketStart(CarbonImmutable::parse($day->day))->toDateString();

            if (isset($buckets[$key])) {
                $buckets[$key]['orders'] += (int) $day->orders;
                $buckets[$key]['sales'] += (int) $day->sales;
            }
        }

        return array_values($buckets);
    }

    /**
     * Get every order placed in the period by where it is now, in the order orders move through.
     *
     * @return list<array{status: OrderStatus, label: string, orders: int, value: int, share: float}>
     */
    public function byStatus(ReportPeriod $period): array
    {
        $counts = $this->orders($period)
            ->select('status')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as value')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $placed = (int) $counts->sum('orders');
        $rows = [];

        foreach (OrderStatus::cases() as $status) {
            $row = $counts->get($status->value);

            if ($row !== null) {
                $rows[] = [
                    'status' => $status,
                    'label' => $status->label(),
                    'orders' => (int) $row->orders,
                    'value' => (int) $row->value,
                    'share' => (int) $row->orders / $placed,
                ];
            }
        }

        return $rows;
    }

    /**
     * Get orders and sales for each delivery area.
     *
     * @return list<array{label: string, orders: int, sales: int}>
     */
    public function byArea(ReportPeriod $period): array
    {
        return $this->rows(
            $this->withTotals($this->orders($period))->addSelect('delivery_zone_name_en as label')->groupBy('delivery_zone_name_en'),
        );
    }

    /**
     * Get orders and sales by where they came from: campaign tags, ad clicks, other sites,
     * or the team's own channels (WhatsApp, Instagram, phone) for orders entered by hand.
     *
     * @return list<array{label: string, orders: int, sales: int}>
     */
    public function bySource(ReportPeriod $period): array
    {
        $groups = $this->withTotals($this->orders($period))
            ->addSelect('channel', 'utm_source', 'utm_medium', 'click_id_type', 'referrer')
            ->groupBy('channel', 'utm_source', 'utm_medium', 'click_id_type', 'referrer')
            ->get();

        $sources = [];

        foreach ($groups as $group) {
            $channel = OrderChannel::tryFrom((string) $group->channel) ?? OrderChannel::Web;
            $label = $channel === OrderChannel::Web
                ? (Order::describeSource($group->utm_source, $group->utm_medium, $group->click_id_type, $group->referrer) ?? 'Direct or unknown')
                : $channel->label().' (entered by the team)';

            $sources[$label] ??= ['label' => $label, 'orders' => 0, 'sales' => 0];
            $sources[$label]['orders'] += (int) $group->orders;
            $sources[$label]['sales'] += (int) $group->sales;
        }

        return $this->ranked(array_values($sources));
    }

    /**
     * Get orders and sales for each campaign ("utm_campaign").
     *
     * @return list<array{label: string, orders: int, sales: int}>
     */
    public function byCampaign(ReportPeriod $period): array
    {
        return $this->rows(
            $this->withTotals($this->orders($period))
                ->addSelect('utm_campaign as label')
                ->whereNotNull('utm_campaign')
                ->groupBy('utm_campaign'),
        );
    }

    /**
     * Get orders, the discount given and sales for each discount code.
     *
     * @return list<array{label: string, orders: int, sales: int, discount: int}>
     */
    public function byCoupon(ReportPeriod $period): array
    {
        return $this->rows(
            $this->withTotals($this->orders($period))
                ->addSelect('coupon_code as label')
                ->selectRaw('COALESCE(SUM(CASE WHEN status NOT IN (?, ?) THEN discount_total END), 0) as discount', self::NOT_KEPT)
                ->whereNotNull('coupon_code')
                ->groupBy('coupon_code'),
            ['discount'],
        );
    }

    /**
     * Get the pieces sold and their value for each product, best sellers first.
     *
     * @return list<array{label: string, orders: int, pieces: int, sales: int}>
     */
    public function products(ReportPeriod $period): array
    {
        return $this->itemRows(
            $this->keptItems($period)
                ->addSelect('order_items.product_name_en as label')
                ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders')
                ->groupBy('order_items.product_id', 'order_items.product_name_en'),
            ['orders'],
        );
    }

    /**
     * Get the pieces sold and their value for each color, across products.
     *
     * @return list<array{label: string, hex: string, pieces: int, sales: int}>
     */
    public function colors(ReportPeriod $period): array
    {
        return $this->itemRows(
            $this->keptItems($period)
                ->addSelect('order_items.color_name_en as label')
                ->selectRaw('MAX(order_items.color_hex) as hex')
                ->groupBy('order_items.color_name_en'),
            textColumns: ['hex'],
        );
    }

    /**
     * Get the pieces sold and their value for each size, across products.
     *
     * @return list<array{label: string, pieces: int, sales: int}>
     */
    public function sizes(ReportPeriod $period): array
    {
        return $this->itemRows(
            $this->keptItems($period)
                ->addSelect('order_items.size_name_en as label')
                ->groupBy('order_items.size_name_en'),
        );
    }

    /**
     * Get the best-selling colors and sizes of each product with the stock left, to see what to restock.
     *
     * @return list<array{label: string, product: string, color: string, size: string, sku: string, pieces: int, sales: int, stock: int|null}>
     */
    public function bestSellers(ReportPeriod $period, ?int $limit = null): array
    {
        $rows = $this->keptItems($period)
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->addSelect('order_items.product_name_en as product', 'order_items.color_name_en as color', 'order_items.size_name_en as size')
            ->selectRaw('MAX(order_items.sku) as sku')
            ->selectRaw('MAX(product_variants.stock) as stock')
            ->selectRaw('SUM(order_items.quantity) as pieces')
            ->selectRaw('SUM(order_items.line_total) as sales')
            ->groupBy('order_items.product_variant_id', 'order_items.product_name_en', 'order_items.color_name_en', 'order_items.size_name_en')
            ->orderByDesc('pieces')
            ->orderByDesc('sales')
            ->when($limit !== null, fn (Builder $query) => $query->limit($limit))
            ->get();

        return $rows->map(fn (object $row): array => [
            'label' => "{$row->product} – {$row->color} / {$row->size}",
            'product' => (string) $row->product,
            'color' => (string) $row->color,
            'size' => (string) $row->size,
            'sku' => (string) $row->sku,
            'pieces' => (int) $row->pieces,
            'sales' => (int) $row->sales,
            'stock' => $row->stock === null ? null : (int) $row->stock,
        ])->all();
    }

    /**
     * Orders placed in the period.
     */
    private function orders(ReportPeriod $period): Builder
    {
        return DB::table('orders')->whereBetween('placed_at', [$period->from, $period->to]);
    }

    /**
     * Add the order count (not cancelled) and sales (not cancelled or returned) to a grouped order query.
     */
    private function withTotals(Builder $query): Builder
    {
        return $query
            ->selectRaw('COUNT(CASE WHEN status <> ? THEN 1 END) as orders', [OrderStatus::Cancelled->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN status NOT IN (?, ?) THEN grand_total END), 0) as sales', self::NOT_KEPT);
    }

    /**
     * Pieces of the orders placed in the period that the store kept.
     */
    private function keptItems(ReportPeriod $period): Builder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.placed_at', [$period->from, $period->to])
            ->whereNotIn('orders.status', self::NOT_KEPT);
    }

    /**
     * Cash marked collected in the period, leaving out orders returned since.
     */
    private function cashCollected(ReportPeriod $period): int
    {
        return (int) DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.status', PaymentStatus::Paid->value)
            ->whereBetween('payments.paid_at', [$period->from, $period->to])
            ->where('orders.status', '<>', OrderStatus::Returned->value)
            ->sum('payments.amount');
    }

    /**
     * Read grouped order rows, leaving out groups with only cancelled orders, best first.
     *
     * @param  list<string>  $amountColumns  extra whole-number columns to read
     * @return list<array<string, mixed>>
     */
    private function rows(Builder $query, array $amountColumns = []): array
    {
        $rows = $query->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'orders' => (int) $row->orders,
                'sales' => (int) $row->sales,
                ...collect($amountColumns)->mapWithKeys(fn (string $column): array => [$column => (int) $row->{$column}])->all(),
            ])
            ->all();

        return $this->ranked($rows);
    }

    /**
     * Read grouped item rows, most pieces first.
     *
     * @param  list<string>  $amountColumns  extra whole-number columns to read
     * @param  list<string>  $textColumns  extra text columns to read
     * @return list<array<string, mixed>>
     */
    private function itemRows(Builder $query, array $amountColumns = [], array $textColumns = []): array
    {
        return $query
            ->selectRaw('SUM(order_items.quantity) as pieces')
            ->selectRaw('SUM(order_items.line_total) as sales')
            ->orderByDesc('pieces')
            ->orderByDesc('sales')
            ->orderBy('label')
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                ...collect($amountColumns)->mapWithKeys(fn (string $column): array => [$column => (int) $row->{$column}])->all(),
                ...collect($textColumns)->mapWithKeys(fn (string $column): array => [$column => (string) $row->{$column}])->all(),
                'pieces' => (int) $row->pieces,
                'sales' => (int) $row->sales,
            ])
            ->all();
    }

    /**
     * Drop groups with only cancelled orders and sort by sales, then orders, then name.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function ranked(array $rows): array
    {
        $rows = array_values(array_filter($rows, fn (array $row): bool => $row['orders'] > 0));

        usort($rows, fn (array $a, array $b): int => [$b['sales'], $b['orders'], $a['label']] <=> [$a['sales'], $a['orders'], $b['label']]);

        return $rows;
    }
}
