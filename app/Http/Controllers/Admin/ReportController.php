<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SalesReport;
use App\Support\Money;
use App\Support\ReportPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Sales reports for the owner: headline figures against the period before, sales over time,
 * and orders broken down by status, source, area, campaign, discount code, product, color and size.
 */
class ReportController extends Controller
{
    /**
     * Rows shown in each breakdown; the CSV download has them all.
     */
    public const ROWS_SHOWN = 10;

    public function __invoke(Request $request, SalesReport $report): View
    {
        $period = ReportPeriod::fromRequest($request);
        $summary = $report->summary($period);
        $before = $report->summary($period->previous());
        $comparedTo = 'vs the '.($period->days() === 1 ? 'day' : $period->days().' days').' before';

        return view('admin.reports.index', [
            'period' => $period,
            'summary' => $summary,
            'changes' => [
                'sales' => self::change($summary['sales'], $before['sales'], $comparedTo),
                'orders' => self::change($summary['orders'], $before['orders'], $comparedTo),
                'averageOrder' => self::change($summary['averageOrder'], $before['averageOrder'], $comparedTo),
                'pieces' => self::change($summary['pieces'], $before['pieces'], $comparedTo),
            ],
            'chart' => array_map(fn (array $bucket): array => [
                'label' => $bucket['label'],
                'axisLabel' => $period->bucketAxisLabel($bucket['start']),
                'value' => $bucket['sales'] / 100,
                'valueText' => Money::format($bucket['sales']),
                'detail' => $bucket['orders'].' '.str('order')->plural($bucket['orders']),
            ], $report->salesOverTime($period)),
            'statuses' => $report->byStatus($period),
            'sources' => $report->bySource($period),
            'areas' => $report->byArea($period),
            'campaigns' => $report->byCampaign($period),
            'coupons' => $report->byCoupon($period),
            'products' => $report->products($period),
            'colors' => $report->colors($period),
            'sizes' => $report->sizes($period),
            'bestSellers' => $report->bestSellers($period, self::ROWS_SHOWN),
        ]);
    }

    /**
     * Describe how a figure moved since the period before, or null when there is nothing to compare with.
     *
     * @return array{text: string, direction: string, good: bool|null}|null
     */
    private static function change(int $current, int $previous, string $comparedTo): ?array
    {
        if ($previous === 0) {
            return null;
        }

        $percent = (int) round(($current - $previous) / $previous * 100);
        $direction = $percent <=> 0;

        return [
            'text' => ($percent > 0 ? '+' : '').$percent.'% '.$comparedTo,
            'direction' => match ($direction) {
                1 => 'up',
                -1 => 'down',
                default => 'flat',
            },
            'good' => $direction === 0 ? null : $direction === 1,
        ];
    }
}
