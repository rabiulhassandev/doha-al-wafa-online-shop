<?php

namespace App\Models;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Support\Money;
use App\Support\QatarPhone;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A discount code. Uses on cancelled orders do not count towards its limits.
 *
 * Problem messages are written for shoppers, in the current language.
 */
#[Fillable([
    'code', 'description', 'type', 'value', 'min_subtotal', 'starts_at', 'ends_at',
    'max_uses', 'max_uses_per_phone', 'first_order_only', 'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'value' => 0,
        'first_order_only' => false,
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'min_subtotal' => 'integer',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'max_uses' => 'integer',
            'max_uses_per_phone' => 'integer',
            'first_order_only' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Write a code the way it is stored: upper case, without spaces.
     */
    public static function normalizeCode(string $code): string
    {
        return Str::upper(preg_replace('/\s+/', '', $code));
    }

    /**
     * Find a coupon by a code as a shopper typed it.
     */
    public static function findByCode(?string $code): ?self
    {
        return filled($code) ? static::query()->firstWhere('code', self::normalizeCode($code)) : null;
    }

    /**
     * Get every use of the coupon.
     *
     * @return HasMany<CouponRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Get the orders placed with the code.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the uses that count towards the limits: those on orders that were not cancelled.
     *
     * @return HasMany<CouponRedemption, $this>
     */
    public function countedRedemptions(): HasMany
    {
        return $this->redemptions()->whereHas('order', fn (Builder $query) => $query->whereNot('status', OrderStatus::Cancelled));
    }

    /**
     * Explain why nobody can use the code right now, or return null when it is available.
     */
    public function availabilityProblem(): ?string
    {
        $replace = ['code' => $this->code];

        return match (true) {
            ! $this->is_active => __('The code :code is not valid.', $replace),
            $this->starts_at !== null && $this->starts_at->isFuture() => __('The code :code is not active yet.', $replace),
            $this->ends_at !== null && $this->ends_at->isPast() => __('The code :code has expired.', $replace),
            $this->max_uses !== null && $this->countedRedemptions()->count() >= $this->max_uses => __('The code :code has been fully used.', $replace),
            default => null,
        };
    }

    /**
     * Explain why the code does not apply to a bag of this value, or return null when it does.
     */
    public function problemFor(int $subtotal): ?string
    {
        if (($problem = $this->availabilityProblem()) !== null) {
            return $problem;
        }

        return $this->min_subtotal !== null && $subtotal < $this->min_subtotal
            ? __('Add :amount more to use the code :code.', ['amount' => Money::format($this->min_subtotal - $subtotal, app()->getLocale()), 'code' => $this->code])
            : null;
    }

    /**
     * Explain why this customer cannot use the code, or return null when they can.
     */
    public function problemForPhone(QatarPhone $phone): ?string
    {
        $replace = ['code' => $this->code];

        if ($this->first_order_only && Order::query()->where('customer_phone', $phone->e164())->whereNot('status', OrderStatus::Cancelled)->exists()) {
            return __('The code :code is for first orders only.', $replace);
        }

        if ($this->max_uses_per_phone !== null && $this->countedRedemptions()->where('phone', $phone->e164())->count() >= $this->max_uses_per_phone) {
            return __('You have already used the code :code.', $replace);
        }

        return null;
    }

    /**
     * Get the discount on a bag, in dirhams. Free delivery codes take off the delivery fee once the area is known.
     */
    public function discountFor(int $subtotal, ?int $deliveryFee): int
    {
        return match ($this->type) {
            CouponType::Percent => intdiv($subtotal * min(100, $this->value), 100),
            CouponType::Fixed => min($this->value, $subtotal),
            CouponType::FreeDelivery => $deliveryFee ?? 0,
        };
    }

    /**
     * Describe what the code gives, in the current language: "10% off", "QAR 20 off" or "Free delivery".
     */
    public function benefit(): string
    {
        return match ($this->type) {
            CouponType::Percent => __(':percent% off', ['percent' => $this->value]),
            CouponType::Fixed => __(':amount off', ['amount' => Money::format($this->value, app()->getLocale())]),
            CouponType::FreeDelivery => __('Free delivery'),
        };
    }
}
