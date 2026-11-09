<?php

namespace App\Services;

use App\Enums\CartLineStatus;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Models\ProductVariant;
use App\Support\CartLine;
use App\Support\CartSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * The shopper's bag: finding it from the cookie, changing quantities and pricing it.
 */
class CartService
{
    public const COOKIE = 'cart';

    public const DELIVERY_ZONE_SESSION_KEY = 'delivery_zone_id';

    private const COOKIE_MINUTES = Cart::KEEP_DAYS * 24 * 60;

    /**
     * Relations each bag line needs.
     */
    public const LINE_RELATIONS = [
        'productVariant.product',
        'productVariant.size',
        'productVariant.productColor.color',
        'productVariant.productColor.images',
    ];

    public function __construct(private readonly Settings $settings) {}

    /**
     * Find the shopper's bag from their cookie.
     */
    public function current(Request $request): ?Cart
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) && $token !== '' ? Cart::query()->firstWhere('token', $token) : null;
    }

    /**
     * Find the shopper's bag, or start one and remember it in a cookie.
     */
    public function currentOrCreate(Request $request): Cart
    {
        $cart = $this->current($request);

        if ($cart !== null) {
            return $cart;
        }

        $cart = Cart::query()->create(['token' => Str::random(48)]);
        Cookie::queue(self::COOKIE, $cart->token, self::COOKIE_MINUTES);

        return $cart;
    }

    /**
     * Count the pieces in the shopper's bag.
     */
    public function count(Request $request): int
    {
        return (int) $this->current($request)?->items()->sum('quantity');
    }

    /**
     * Get the most of one color and size a shopper can order right now.
     */
    public function maxQuantity(ProductVariant $variant): int
    {
        if (! $this->isPurchasable($variant)) {
            return 0;
        }

        return min($variant->stock, (int) $this->settings->get('max_quantity_per_item'));
    }

    /**
     * Add pieces of a color and size, keeping the total within stock and the per-item limit.
     *
     * @return array{quantity: int, limited: bool} the quantity now in the bag, and whether it was capped
     */
    public function add(Cart $cart, ProductVariant $variant, int $quantity): array
    {
        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
        $wanted = ($item->quantity ?? 0) + max(1, $quantity);
        $allowed = min($wanted, $this->maxQuantity($variant));

        if ($allowed > 0) {
            $item->quantity = $allowed;
            $item->save();
        }

        return ['quantity' => $allowed, 'limited' => $allowed < $wanted];
    }

    /**
     * Change how many of a line are in the bag. Zero, or nothing left in stock, removes it.
     *
     * @return array{quantity: int, limited: bool}
     */
    public function setQuantity(CartItem $item, int $quantity): array
    {
        $allowed = min(max(0, $quantity), $this->maxQuantity($item->productVariant));

        if ($allowed === 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $allowed]);
        }

        return ['quantity' => $allowed, 'limited' => $allowed < $quantity];
    }

    /**
     * Price the bag. Lines with more pieces than are left are lowered to what is in stock.
     */
    public function summary(?Cart $cart, ?DeliveryZone $deliveryZone): CartSummary
    {
        $items = $cart?->items()->with(self::LINE_RELATIONS)->orderBy('id')->get() ?? collect();

        return new CartSummary(
            $items->map(fn (CartItem $item): CartLine => $this->line($item->productVariant, $item->quantity, $item))->values(),
            $deliveryZone,
            $this->storeFreeDeliveryThreshold(),
        );
    }

    /**
     * Price a single "Order now" item without touching the bag.
     */
    public function summaryFor(ProductVariant $variant, int $quantity, ?DeliveryZone $deliveryZone): CartSummary
    {
        $variant->loadMissing(['product', 'size', 'productColor.color', 'productColor.images']);

        return new CartSummary(collect([$this->line($variant, $quantity)]), $deliveryZone, $this->storeFreeDeliveryThreshold());
    }

    /**
     * Get the delivery area the shopper picked during this visit.
     */
    public function deliveryZone(Request $request): ?DeliveryZone
    {
        $zoneId = $request->session()->get(self::DELIVERY_ZONE_SESSION_KEY);

        return $zoneId === null ? null : DeliveryZone::query()->active()->find($zoneId);
    }

    /**
     * Get the store-wide order value for free delivery, in dirhams.
     */
    public function storeFreeDeliveryThreshold(): ?int
    {
        $threshold = $this->settings->get('delivery_free_over');

        return $threshold === null ? null : (int) $threshold;
    }

    /**
     * Price a color and size from the live product. More pieces than can be ordered are lowered
     * to what is left, in the bag line too when one is given. Expects the variant's product,
     * product color and size to be loaded or loadable.
     */
    public function line(ProductVariant $variant, int $quantity, ?CartItem $item = null): CartLine
    {
        $maxQuantity = $this->maxQuantity($variant);
        $status = match (true) {
            $maxQuantity === 0 => CartLineStatus::Unavailable,
            $quantity > $maxQuantity => CartLineStatus::Reduced,
            default => CartLineStatus::Available,
        };

        if ($status === CartLineStatus::Reduced) {
            $quantity = $maxQuantity;
            $item?->update(['quantity' => $maxQuantity]);
        }

        $unitPrice = $variant->price ?? $variant->product->price;
        $compareAtPrice = $variant->compare_at_price ?? $variant->product->compare_at_price;

        return new CartLine(
            variant: $variant,
            quantity: $quantity,
            unitPrice: $unitPrice,
            compareAtPrice: $compareAtPrice !== null && $compareAtPrice > $unitPrice ? $compareAtPrice : null,
            maxQuantity: $maxQuantity,
            status: $status,
            item: $item,
        );
    }

    /**
     * Determine whether a color and size can be ordered: sold, in stock, and on a shown product and color.
     * Expects the product and product color to be loaded or loadable.
     */
    private function isPurchasable(ProductVariant $variant): bool
    {
        return $variant->is_active
            && $variant->stock > 0
            && $variant->productColor->is_active
            && $variant->product->status === ProductStatus::Active;
    }
}
