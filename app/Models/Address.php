<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A delivery address saved on a customer account, in Qatar's blue plate format.
 */
#[Fillable(['label', 'delivery_zone_id', 'zone_number', 'street_number', 'building_number', 'unit', 'landmark', 'is_default'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_default' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get the account the address belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the delivery area, while it still exists.
     *
     * @return BelongsTo<DeliveryZone, $this>
     */
    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
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
     * Get the fields the checkout form fills in when this address is chosen.
     *
     * @return array{delivery_zone_id: string, zone_number: string, street_number: string, building_number: string, unit: string, landmark: string}
     */
    public function checkoutFields(): array
    {
        return [
            'delivery_zone_id' => (string) $this->delivery_zone_id,
            'zone_number' => $this->zone_number,
            'street_number' => $this->street_number,
            'building_number' => $this->building_number,
            'unit' => (string) $this->unit,
            'landmark' => (string) $this->landmark,
        ];
    }
}
