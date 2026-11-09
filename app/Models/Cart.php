<?php

namespace App\Models;

use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A shopper's bag, found through a long-lived browser cookie holding its token.
 */
#[Fillable(['token', 'user_id'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory, Prunable;

    /**
     * Bags untouched for this many days are deleted by "php artisan model:prune".
     */
    public const KEEP_DAYS = 30;

    /**
     * Get the items in the bag.
     *
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the bags that have been abandoned long enough to delete.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()->where('updated_at', '<', now()->subDays(self::KEEP_DAYS));
    }
}
