<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Money;
use App\Support\OrderFilters;
use App\Support\SpreadsheetCsv;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderExportController extends Controller
{
    private const COLUMNS = [
        'Order', 'Placed', 'Status', 'Payment', 'Channel', 'Customer', 'Phone', 'Email',
        'Area', 'Address', 'Pieces', 'Subtotal (QAR)', 'Delivery (QAR)', 'Discount (QAR)', 'Coupon', 'Total (QAR)',
        'Source', 'Campaign',
    ];

    /**
     * Download the filtered orders as a CSV file that opens in Excel, Arabic names included.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $filters = OrderFilters::fromRequest($request);
        $query = $filters->apply(Order::query())->withSum('items', 'quantity')->latest('placed_at')->orderByDesc('id');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            // A byte order mark tells Excel the file is UTF-8.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, self::COLUMNS, escape: '');

            foreach ($query->lazy(200) as $order) {
                fputcsv($output, [
                    $order->number,
                    $order->placed_at->format('Y-m-d H:i'),
                    $order->status->label(),
                    $order->payment_status->label(),
                    $order->channel->label(),
                    SpreadsheetCsv::text($order->customer_name),
                    SpreadsheetCsv::text($order->customer_phone),
                    SpreadsheetCsv::text($order->customer_email),
                    SpreadsheetCsv::text($order->delivery_zone_name_en),
                    SpreadsheetCsv::text($order->plateAddress()),
                    (int) $order->items_sum_quantity,
                    Money::toInput($order->subtotal),
                    Money::toInput($order->delivery_fee),
                    Money::toInput($order->discount_total),
                    SpreadsheetCsv::text($order->coupon_code),
                    Money::toInput($order->grand_total),
                    SpreadsheetCsv::text($order->sourceLabel()),
                    SpreadsheetCsv::text($order->utm_campaign),
                ], escape: '');
            }

            fclose($output);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
