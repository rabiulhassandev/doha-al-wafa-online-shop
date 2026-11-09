<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use App\Support\DeliveryEstimate;
use Carbon\CarbonImmutable;
use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An area of Qatar the store delivers to, with its fee (in dirhams) and delivery time in working days.
 */
#[Fillable(['name_en', 'name_ar', 'fee', 'free_over', 'eta_min_days', 'eta_max_days', 'sort_order', 'is_active'])]
class DeliveryZone extends Model
{
    /** @use HasFactory<DeliveryZoneFactory> */
    use HasFactory, Translatable;

    /**
     * How many delivery days shoppers can choose from as their preferred date.
     */
    public const CHOOSABLE_DAYS = 7;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'eta_min_days' => 1,
        'eta_max_days' => 3,
        'sort_order' => 0,
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
            'fee' => 'integer',
            'free_over' => 'integer',
            'eta_min_days' => 'integer',
            'eta_max_days' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the earliest and latest delivery dates for an order placed at the given time.
     *
     * @param  list<int>  $daysOff  ISO weekdays with no delivery
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    public function deliveryWindow(CarbonImmutable $orderedAt, array $daysOff): array
    {
        return DeliveryEstimate::between($orderedAt, $this->eta_min_days, $this->eta_max_days, $daysOff);
    }

    /**
     * Get the days a shopper may pick as their preferred delivery date.
     *
     * @param  list<int>  $daysOff  ISO weekdays with no delivery
     * @return list<CarbonImmutable>
     */
    public function choosableDeliveryDays(CarbonImmutable $orderedAt, array $daysOff): array
    {
        return DeliveryEstimate::choosableDays($orderedAt, $this->eta_min_days, self::CHOOSABLE_DAYS, $daysOff);
    }

    /**
     * Get the order value from which delivery is free: this area's own amount, or the store-wide one.
     */
    public function freeDeliveryThreshold(?int $storeThreshold): ?int
    {
        return $this->free_over ?? $storeThreshold;
    }

    /**
     * Get the delivery fee for an order of the given value, in dirhams.
     */
    public function feeFor(int $subtotal, ?int $storeThreshold): int
    {
        $threshold = $this->freeDeliveryThreshold($storeThreshold);

        return $threshold !== null && $subtotal >= $threshold ? 0 : $this->fee;
    }

    /**
     * Scope the query to areas shoppers can choose.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Order areas as the owner arranged them.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name_en')->orderBy('id');
    }
}
