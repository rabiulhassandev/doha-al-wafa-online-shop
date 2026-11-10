<?php

namespace App\Models;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\Translatable;
use App\Support\Digits;
use App\Support\QatarPhone;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

/**
 * A placed order. Amounts are in dirhams. Customer, address and delivery area are copied onto the
 * order when it is placed, and each line keeps a copy of what was sold.
 */
#[Fillable([
    'user_id', 'checkout_token', 'channel', 'status', 'payment_method', 'payment_status', 'currency',
    'subtotal', 'discount_total', 'coupon_id', 'coupon_code', 'delivery_fee', 'tax_total', 'grand_total',
    'customer_name', 'customer_phone', 'customer_email',
    'delivery_zone_id', 'delivery_zone_name_en', 'delivery_zone_name_ar',
    'zone_number', 'street_number', 'building_number', 'unit', 'landmark', 'latitude', 'longitude',
    'preferred_delivery_date', 'delivery_estimate_from', 'delivery_estimate_to',
    'customer_note', 'admin_note', 'locale', 'ip_address', 'user_agent', 'placed_at',
    'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'click_id_type', 'click_id',
    'referrer', 'landing_url', 'short_link_id', 'ad_tracking_allowed', 'tracking_ids',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, Translatable;

    /**
     * Order numbers read "DW-10001", "DW-10002", …
     */
    public const NUMBER_PREFIX = 'DW-';

    private const FIRST_NUMBER = 10000;

    /**
     * How long the private link to the thank-you page works.
     */
    private const CONFIRMATION_LINK_DAYS = 30;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => OrderChannel::class,
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'delivery_fee' => 'integer',
            'tax_total' => 'integer',
            'grand_total' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'preferred_delivery_date' => 'immutable_date',
            'delivery_estimate_from' => 'immutable_date',
            'delivery_estimate_to' => 'immutable_date',
            'placed_at' => 'immutable_datetime',
            'ad_tracking_allowed' => 'boolean',
            'tracking_ids' => 'array',
        ];
    }

    /**
     * Get the order number for an order ID.
     */
    public static function numberFor(int $id): string
    {
        return self::NUMBER_PREFIX.(self::FIRST_NUMBER + $id);
    }

    /**
     * Get the private, signed link to the order's page, in the language it was placed in unless another is given.
     */
    public function confirmationUrl(?string $locale = null): string
    {
        return URL::temporarySignedRoute(
            'orders.thank-you',
            now()->addDays(self::CONFIRMATION_LINK_DAYS),
            ['locale' => $locale ?? $this->locale, 'order' => $this],
        );
    }

    /**
     * Get the customer who placed the order.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the account the order was placed from, when the shopper was signed in.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the delivery area, while it still exists. Its name is also copied onto the order.
     *
     * @return BelongsTo<DeliveryZone, $this>
     */
    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    /**
     * Get the lines of the order.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payments recorded for the order.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the short link the shopper came through, while it still exists.
     *
     * @return BelongsTo<ShortLink, $this>
     */
    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class);
    }

    /**
     * Describe where the order came from for the team, e.g. "instagram / social", "TikTok ad" or "google.com".
     */
    public function sourceLabel(): ?string
    {
        if (filled($this->utm_source)) {
            return collect([$this->utm_source, $this->utm_medium])->filter()->implode(' / ');
        }

        if ($this->click_id_type !== null) {
            return match ($this->click_id_type) {
                'meta' => 'Meta ad',
                'tiktok' => 'TikTok ad',
                'snapchat' => 'Snapchat ad',
                'google' => 'Google ad',
                default => $this->click_id_type,
            };
        }

        return $this->referrer ? (parse_url($this->referrer, PHP_URL_HOST) ?: $this->referrer) : null;
    }

    /**
     * Get the coupon used, while it still exists. Its code is also copied onto the order.
     *
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the status changes, oldest first.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->oldest()->orderBy('id');
    }

    /**
     * Find an order from the number a customer typed, such as "DW-10001", "dw 10001" or "10001".
     */
    public static function findByTypedNumber(string $typed): ?self
    {
        $digits = preg_replace('/\D+/', '', Digits::toWestern($typed));

        return $digits === '' ? null : static::query()->firstWhere('number', self::NUMBER_PREFIX.$digits);
    }

    /**
     * Get the customer's phone number written for people, e.g. "+974 5555 1234".
     */
    public function formattedPhone(): string
    {
        return QatarPhone::tryParse($this->customer_phone)?->formatted() ?? $this->customer_phone;
    }

    /**
     * Get the blue plate address in the current language, e.g. "Zone 45, Street 330, Building 12".
     */
    public function plateAddress(): string
    {
        return __('Zone :zone, Street :street, Building :building', [
            'zone' => $this->zone_number,
            'street' => $this->street_number,
            'building' => $this->building_number,
        ]);
    }

    /**
     * Describe when the order should arrive, in the current language: the shopper's preferred
     * date when they chose one, otherwise the estimate made when the order was placed.
     */
    public function deliveryPromise(): string
    {
        $locale = app()->getLocale();
        $format = fn ($date): string => $date->locale($locale)->translatedFormat('l j F');

        if ($this->preferred_delivery_date !== null) {
            return __('Your preferred delivery date: :date', ['date' => $format($this->preferred_delivery_date)]);
        }

        return $this->delivery_estimate_from->isSameDay($this->delivery_estimate_to)
            ? __('Expected delivery on :date', ['date' => $format($this->delivery_estimate_from)])
            : __('Expected delivery between :from and :to', [
                'from' => $format($this->delivery_estimate_from),
                'to' => $format($this->delivery_estimate_to),
            ]);
    }

    /**
     * Count the pieces in the order.
     */
    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /**
     * Get a Google Maps link to the pin the customer shared, if they shared one.
     */
    public function mapUrl(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
    }
}
