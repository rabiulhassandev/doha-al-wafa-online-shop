<?php

namespace App\Http\Controllers\Admin;

use App\Actions\PlaceOrder;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\OrderNotPlacedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Settings;
use App\Support\Money;
use App\Support\OrderFilters;
use App\Support\QatarPhone;
use App\Support\VariantOption;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Messages the team can send on WhatsApp, written in the customer's language when the order is shown.
     */
    private const WHATSAPP_MESSAGES = [
        'Ask to confirm the order' => 'Hello :name, this is :store. We received your order :number for :total, paid in cash on delivery. Can you confirm it, please?',
        'Say it is on the way' => 'Hello :name, your order :number from :store is on its way to you today. Please have :total ready in cash.',
        'Arrange a missed delivery' => 'Hello :name, our driver could not reach you with your order :number from :store. When is a good time to deliver it?',
    ];

    /**
     * List orders, newest first, with a tab per status.
     */
    public function index(Request $request): View
    {
        $filters = OrderFilters::fromRequest($request);

        $orders = $filters->apply(Order::query())
            ->withSum('items', 'quantity')
            ->latest('placed_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $statusCounts = $filters->apply(Order::query(), withStatus: false)
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (mixed $total): int => (int) $total);

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'deliveryZones' => DeliveryZone::query()->ordered()->pluck('name_en', 'id'),
        ]);
    }

    /**
     * Show the form for entering an order taken on WhatsApp, Instagram or the phone.
     */
    public function create(Request $request): View
    {
        $oldLines = collect($request->old('lines', []))->filter(fn (mixed $line): bool => is_array($line) && isset($line['variant_id']));
        $variants = ProductVariant::query()
            ->with(['product', 'productColor.color', 'productColor.images', 'size'])
            ->findMany($oldLines->pluck('variant_id'))
            ->keyBy('id');

        return view('admin.orders.create', [
            'deliveryZones' => DeliveryZone::query()->active()->ordered()->get(),
            'channels' => OrderChannel::manualOptions(),
            'lines' => $oldLines
                ->filter(fn (array $line): bool => $variants->has($line['variant_id']))
                ->map(fn (array $line): array => [...VariantOption::from($variants->get($line['variant_id'])), 'quantity' => (int) ($line['quantity'] ?? 1)])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Enter an order by hand. It starts confirmed and takes the stock straight away.
     */
    public function store(StoreOrderRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        try {
            $order = $placeOrder->handle($request->quantities(), $request->details(), placedBy: $request->user());
        } catch (OrderNotPlacedException $exception) {
            return back()->withInput()->withErrors(['lines' => $exception->getMessage()]);
        }

        if (filled($request->validated('admin_note'))) {
            $order->update(['admin_note' => $request->validated('admin_note')]);
        }

        return to_route('admin.orders.show', $order)->with('status', "Order {$order->number} was created.");
    }

    /**
     * Show an order with everything needed to confirm, pack and deliver it.
     */
    public function show(Order $order, Settings $settings): View
    {
        $order->load([
            'items.productVariant',
            'statusHistories.user',
            'payments',
            'customer' => fn ($query) => $query->withCount('orders'),
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'timeline' => $this->timeline($order),
            'whatsAppMessages' => $this->whatsAppMessages($order, (string) $settings->get('store_name')),
            'canCollectCash' => $order->payment_status === PaymentStatus::Pending
                && in_array($order->status, [OrderStatus::OutForDelivery, OrderStatus::Delivered], true),
        ]);
    }

    /**
     * Put the order's events in time order: placed, each status change and the cash collected.
     *
     * @return list<array{at: CarbonInterface, title: string, note: string|null, by: string|null}>
     */
    private function timeline(Order $order): array
    {
        $events = collect([[
            'at' => $order->placed_at,
            'title' => $order->channel === OrderChannel::Web ? 'Placed on the website' : 'Taken on '.$order->channel->label(),
            'note' => null,
            'by' => null,
        ]]);

        foreach ($order->statusHistories as $history) {
            $events->push(['at' => $history->created_at, 'title' => $history->to_status->label(), 'note' => $history->note, 'by' => $history->user?->name]);
        }

        foreach ($order->payments->whereNotNull('paid_at') as $payment) {
            $events->push(['at' => $payment->paid_at, 'title' => 'Cash collected', 'note' => null, 'by' => null]);
        }

        return $events->sortBy(fn (array $event): int => $event['at']->getTimestamp())->values()->all();
    }

    /**
     * Get ready-written WhatsApp messages to the customer, in their language.
     *
     * @return list<array{label: string, url: string}>
     */
    private function whatsAppMessages(Order $order, string $storeName): array
    {
        $phone = QatarPhone::tryParse($order->customer_phone);

        if ($phone === null) {
            return [];
        }

        $replace = [
            'name' => $order->customer_name,
            'number' => $order->number,
            'store' => $storeName,
            'total' => Money::format($order->grand_total, $order->locale),
        ];

        return collect(self::WHATSAPP_MESSAGES)
            ->map(fn (string $message, string $label): array => [
                'label' => $label,
                'url' => 'https://wa.me/'.$phone->whatsAppNumber().'?text='.rawurlencode(__($message, $replace, $order->locale)),
            ])
            ->values()
            ->all();
    }
}
