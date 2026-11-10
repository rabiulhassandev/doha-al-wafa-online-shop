<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\SalesReport;
use App\Services\Settings;
use App\Support\Money;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const SOCIAL_SETTINGS = ['instagram_url', 'tiktok_url', 'facebook_url', 'snapchat_url'];

    private const TOP_ROWS = 5;

    private const LOW_STOCK_ROWS = 6;

    /**
     * Show the admin dashboard: the work waiting today, best sellers and low stock for the whole team,
     * and sales and sources for the owner.
     */
    public function __invoke(Request $request, Settings $settings, SalesReport $report): View
    {
        $today = CarbonImmutable::today();
        $lastThirtyDays = ReportPeriod::make($today->subDays(29), $today, '30d');
        $lowStockThreshold = (int) $settings->get('low_stock_threshold');
        $lowStock = ProductVariant::query()
            ->where('is_active', true)
            ->whereBetween('stock', [1, $lowStockThreshold]);
        $todaysOrders = Order::query()->where('placed_at', '>=', $today)->whereNot('status', OrderStatus::Cancelled);

        return view('admin.dashboard', [
            'setupTasks' => $this->setupTasks($settings),
            'orderStats' => [
                'waiting' => Order::query()->where('status', OrderStatus::PendingConfirmation)->count(),
                'todayCount' => (clone $todaysOrders)->count(),
                'todayTotal' => (int) (clone $todaysOrders)->whereNot('status', OrderStatus::Returned)->sum('grand_total'),
                'outForDelivery' => Order::query()->where('status', OrderStatus::OutForDelivery)->count(),
                'lowStock' => (clone $lowStock)->count(),
            ],
            // The same sizes as the inventory's "Low stock" filter.
            'lowStockVariants' => (clone $lowStock)
                ->with(['product', 'productColor.color', 'size'])
                ->orderBy('stock')
                ->orderBy('id')
                ->limit(self::LOW_STOCK_ROWS)
                ->get(),
            'topProducts' => array_slice($report->products($lastThirtyDays), 0, self::TOP_ROWS),
            'topColors' => array_slice($report->colors($lastThirtyDays), 0, self::TOP_ROWS),
            'owner' => $request->user()->can('manage-store') ? $this->ownerFigures($report, $today, $lastThirtyDays) : null,
        ]);
    }

    /**
     * Get the money figures only the owner sees: this month, the last 14 days by day and where orders came from.
     *
     * @return array{month: array<string, mixed>, chart: list<array<string, mixed>>, sources: list<array{label: string, orders: int, sales: int}>}
     */
    private function ownerFigures(SalesReport $report, CarbonImmutable $today, ReportPeriod $lastThirtyDays): array
    {
        $lastFortnight = ReportPeriod::make($today->subDays(13), $today, 'custom', 'day');

        return [
            'month' => $report->summary(ReportPeriod::make($today->startOfMonth(), $today, 'this_month')),
            'chart' => array_map(fn (array $bucket): array => [
                'label' => $bucket['label'],
                'axisLabel' => $lastFortnight->bucketAxisLabel($bucket['start']),
                'value' => $bucket['sales'] / 100,
                'valueText' => Money::format($bucket['sales']),
                'detail' => $bucket['orders'].' '.str('order')->plural($bucket['orders']),
            ], $report->salesOverTime($lastFortnight)),
            'sources' => array_slice($report->bySource($lastThirtyDays), 0, self::TOP_ROWS),
        ];
    }

    /**
     * @return list<array{label: string, done: bool}>
     */
    private function setupTasks(Settings $settings): array
    {
        return [
            ['label' => 'Add the store WhatsApp number', 'done' => filled($settings->get('whatsapp_number'))],
            ['label' => 'Add a contact phone number', 'done' => filled($settings->get('contact_phone'))],
            ['label' => 'Add a contact email', 'done' => filled($settings->get('contact_email'))],
            [
                'label' => 'Link at least one social media account',
                'done' => collect(self::SOCIAL_SETTINGS)->contains(fn (string $key): bool => filled($settings->get($key))),
            ],
        ];
    }
}
