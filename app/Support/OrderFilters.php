<?php

namespace App\Support;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Throwable;

/**
 * The filters of the admin order list, read from the query string. Shared by the list and its CSV export.
 */
final readonly class OrderFilters
{
    public function __construct(
        public ?OrderStatus $status = null,
        public ?PaymentStatus $paymentStatus = null,
        public ?OrderChannel $channel = null,
        public ?int $deliveryZoneId = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public string $search = '',
    ) {}

    /**
     * Read the filters from the request, ignoring values that are not valid.
     */
    public static function fromRequest(Request $request): self
    {
        $zoneId = filter_var($request->query('area'), FILTER_VALIDATE_INT);

        return new self(
            status: OrderStatus::tryFrom((string) $request->query('status')),
            paymentStatus: PaymentStatus::tryFrom((string) $request->query('payment')),
            channel: OrderChannel::tryFrom((string) $request->query('channel')),
            deliveryZoneId: $zoneId === false ? null : $zoneId,
            from: self::date($request->query('from')),
            to: self::date($request->query('to')),
            search: trim((string) $request->query('search')),
        );
    }

    /**
     * Narrow an order query to these filters.
     *
     * @param  Builder<Order>  $query
     * @param  bool  $withStatus  false to count orders by status across every status tab
     * @return Builder<Order>
     */
    public function apply(Builder $query, bool $withStatus = true): Builder
    {
        return $query
            ->when($withStatus && $this->status !== null, fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->paymentStatus !== null, fn (Builder $query) => $query->where('payment_status', $this->paymentStatus))
            ->when($this->channel !== null, fn (Builder $query) => $query->where('channel', $this->channel))
            ->when($this->deliveryZoneId !== null, fn (Builder $query) => $query->where('delivery_zone_id', $this->deliveryZoneId))
            ->when($this->from !== null, fn (Builder $query) => $query->where('placed_at', '>=', $this->from->startOfDay()))
            ->when($this->to !== null, fn (Builder $query) => $query->where('placed_at', '<=', $this->to->endOfDay()))
            ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                $digits = preg_replace('/\D+/', '', Digits::toWestern($this->search));

                $query->where('customer_name', 'like', "%{$this->search}%")
                    ->when($digits !== '', fn (Builder $query) => $query
                        ->orWhere('number', 'like', "%{$digits}%")
                        ->orWhere('customer_phone', 'like', "%{$digits}%"));
            }));
    }

    /**
     * Get the query string for these filters, with any values replaced.
     *
     * @param  array<string, string|null>  $changes
     * @return array<string, string>
     */
    public function query(array $changes = []): array
    {
        return array_filter([
            'status' => $this->status?->value,
            'payment' => $this->paymentStatus?->value,
            'channel' => $this->channel?->value,
            'area' => $this->deliveryZoneId === null ? null : (string) $this->deliveryZoneId,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
            'search' => $this->search,
            ...$changes,
        ], fn (?string $value): bool => $value !== null && $value !== '');
    }

    /**
     * Determine whether any filter besides the status tab is set.
     */
    public function narrowsList(): bool
    {
        return array_diff_key($this->query(), ['status' => true]) !== [];
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }
    }
}
