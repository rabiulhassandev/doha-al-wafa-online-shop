<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SalesReport;
use App\Support\Money;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads one report as a CSV file that opens in Excel, for the same period as the reports page.
 */
class ReportExportController extends Controller
{
    public const REPORTS = ['sales', 'statuses', 'sources', 'areas', 'campaigns', 'coupons', 'products', 'colors', 'sizes', 'best-sellers'];

    public function __invoke(Request $request, string $report, SalesReport $salesReport): StreamedResponse
    {
        $period = ReportPeriod::fromRequest($request);
        [$columns, $rows] = $this->table($report, $period, $salesReport);
        $fileName = "report-{$report}-{$period->from->toDateString()}-to-{$period->to->toDateString()}.csv";

        return response()->streamDownload(function () use ($columns, $rows): void {
            $output = fopen('php://output', 'w');
            // A byte order mark tells Excel the file is UTF-8.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $columns, escape: '');

            foreach ($rows as $row) {
                fputcsv($output, $row, escape: '');
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Get a report's column names and rows, with amounts in QAR.
     *
     * @return array{list<string>, list<list<string|int>>}
     */
    private function table(string $report, ReportPeriod $period, SalesReport $salesReport): array
    {
        return match ($report) {
            'sales' => [
                ['Period', 'Orders', 'Sales (QAR)'],
                array_map(fn (array $row): array => [$row['label'], $row['orders'], Money::toInput($row['sales'])], $salesReport->salesOverTime($period)),
            ],
            'statuses' => [
                ['Status', 'Orders', 'Share of orders (%)', 'Order value (QAR)'],
                array_map(fn (array $row): array => [$row['label'], $row['orders'], round($row['share'] * 100, 1), Money::toInput($row['value'])], $salesReport->byStatus($period)),
            ],
            'sources', 'areas', 'campaigns' => [
                [match ($report) {
                    'sources' => 'Source',
                    'areas' => 'Delivery area',
                    default => 'Campaign',
                }, 'Orders', 'Sales (QAR)'],
                array_map(fn (array $row): array => [$row['label'], $row['orders'], Money::toInput($row['sales'])], match ($report) {
                    'sources' => $salesReport->bySource($period),
                    'areas' => $salesReport->byArea($period),
                    default => $salesReport->byCampaign($period),
                }),
            ],
            'coupons' => [
                ['Discount code', 'Orders', 'Discount given (QAR)', 'Sales (QAR)'],
                array_map(fn (array $row): array => [$row['label'], $row['orders'], Money::toInput($row['discount']), Money::toInput($row['sales'])], $salesReport->byCoupon($period)),
            ],
            'products' => [
                ['Product', 'Orders', 'Pieces', 'Item sales (QAR)'],
                array_map(fn (array $row): array => [$row['label'], $row['orders'], $row['pieces'], Money::toInput($row['sales'])], $salesReport->products($period)),
            ],
            'colors', 'sizes' => [
                [$report === 'colors' ? 'Color' : 'Size', 'Pieces', 'Item sales (QAR)'],
                array_map(fn (array $row): array => [$row['label'], $row['pieces'], Money::toInput($row['sales'])], $report === 'colors' ? $salesReport->colors($period) : $salesReport->sizes($period)),
            ],
            'best-sellers' => [
                ['Product', 'Color', 'Size', 'SKU', 'Pieces', 'Item sales (QAR)', 'In stock now'],
                array_map(fn (array $row): array => [$row['product'], $row['color'], $row['size'], $row['sku'], $row['pieces'], Money::toInput($row['sales']), $row['stock'] ?? ''], $salesReport->bestSellers($period)),
            ],
        };
    }
}
