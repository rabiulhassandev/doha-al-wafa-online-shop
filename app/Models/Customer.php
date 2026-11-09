<?php

namespace App\Models;

use App\Support\QatarPhone;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Someone who ordered, created automatically from their first order. The phone number (E.164) identifies them.
 */
#[Fillable(['name', 'phone', 'email', 'locale', 'is_blocked'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'locale' => 'en',
        'is_blocked' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
        ];
    }

    /**
     * Get the customer's orders.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Determine whether the team blocked this phone number from ordering online.
     */
    public static function isBlocked(QatarPhone $phone): bool
    {
        return static::query()->where('phone', $phone->e164())->where('is_blocked', true)->exists();
    }
}
