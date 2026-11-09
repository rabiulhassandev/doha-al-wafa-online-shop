<?php

namespace App\Support;

use App\Enums\CartLineStatus;
use App\Models\DeliveryZone;
use Illuminate\Support\Collection;

/**
 * The bag (or an "Order now" item) with its totals. All amounts are in dirhams.
 */
final readonly class CartSummary
{
    /**
     * @param  Collection<int, CartLine>  $lines
     */
    public function __construct(
        public Collection $lines,
        public ?DeliveryZone $deliveryZone,
        public ?int $storeFreeDeliveryThreshold,
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    /**
     * Count the pieces that can be ordered.
     */
    public function itemCount(): int
    {
        return $this->lines->filter(fn (CartLine $line): bool => $line->isOrderable())->sum('quantity');
    }

    public function subtotal(): int
    {
        return $this->lines->sum(fn (CartLine $line): int => $line->total());
    }

    /**
     * Get the delivery fee, or null until the shopper chooses their area.
     */
    public function deliveryFee(): ?int
    {
        return $this->deliveryZone?->feeFor($this->subtotal(), $this->storeFreeDeliveryThreshold);
    }

    /**
     * Get the order value from which delivery is free, if there is one.
     */
    public function freeDeliveryThreshold(): ?int
    {
        return $this->deliveryZone
            ? $this->deliveryZone->freeDeliveryThreshold($this->storeFreeDeliveryThreshold)
            : $this->storeFreeDeliveryThreshold;
    }

    /**
     * Get how much more the shopper needs to spend for free delivery, or null when there is no such offer.
     */
    public function amountToFreeDelivery(): ?int
    {
        $threshold = $this->freeDeliveryThreshold();

        return $threshold === null ? null : max(0, $threshold - $this->subtotal());
    }

    public function total(): int
    {
        return $this->subtotal() + ($this->deliveryFee() ?? 0);
    }

    /**
     * Determine whether a line needs the shopper's attention (sold out, or its quantity was lowered).
     */
    public function hasChanges(): bool
    {
        return $this->lines->contains(fn (CartLine $line): bool => $line->status !== CartLineStatus::Available);
    }

    /**
     * Determine whether a sold out line must be removed before ordering.
     */
    public function hasUnavailableLines(): bool
    {
        return $this->lines->contains(fn (CartLine $line): bool => ! $line->isOrderable());
    }
}
