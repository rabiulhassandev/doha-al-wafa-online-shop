<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Support\CartLine;
use App\Support\CartSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Ad pixels (Meta, TikTok, Snapchat) and Google Analytics: which are set up, whether this visitor
 * allowed them, and the events pages send. Product IDs are SKUs, the same as in the catalog feeds,
 * so ads and the catalog match. Purchases carry the same event ID in the browser and on the server,
 * so the platforms count them once.
 */
class Tracking
{
    /**
     * The cookie the cookie bar sets to "accepted" or "declined".
     */
    public const CONSENT_COOKIE = 'tracking_consent';

    /**
     * Cookies the ad platforms' own scripts set in the browser.
     */
    public const PLATFORM_COOKIES = ['_fbp' => 'fbp', '_fbc' => 'fbc', '_ttp' => 'ttp'];

    public function __construct(private readonly Settings $settings) {}

    /**
     * Get the pixel and measurement IDs that are set up.
     *
     * @return array<string, string>
     */
    public function pixelIds(): array
    {
        return array_filter([
            'meta' => (string) $this->settings->get('meta_pixel_id'),
            'tiktok' => (string) $this->settings->get('tiktok_pixel_id'),
            'snap' => (string) $this->settings->get('snap_pixel_id'),
            'ga4' => (string) $this->settings->get('ga4_measurement_id'),
        ], fn (string $id): bool => $id !== '');
    }

    /**
     * Determine whether any pixel is set up, so pages only build events someone receives.
     */
    public function isEnabled(): bool
    {
        return $this->pixelIds() !== [];
    }

    /**
     * Determine whether ad trackers may run for this visitor: they accepted them, or the store does not ask.
     */
    public function allowedFor(Request $request): bool
    {
        return ! $this->settings->get('consent_bar_enabled') || $request->cookie(self::CONSENT_COOKIE) === 'accepted';
    }

    /**
     * Get what the browser needs to load the pixels, or null when none is set up.
     *
     * @return array{pixels: array<string, string>, consentRequired: bool}|null
     */
    public function browserConfig(): ?array
    {
        $pixels = $this->pixelIds();

        return $pixels === [] ? null : ['pixels' => $pixels, 'consentRequired' => (bool) $this->settings->get('consent_bar_enabled')];
    }

    /**
     * Get the IDs the ad platforms' scripts saved in this browser, to match server events with it.
     *
     * @return array<string, string>
     */
    public function browserIds(Request $request): array
    {
        $ids = [];

        foreach (self::PLATFORM_COOKIES as $cookie => $key) {
            $value = $request->cookie($cookie);

            if (is_string($value) && $value !== '' && strlen($value) <= 255) {
                $ids[$key] = $value;
            }
        }

        return $ids;
    }

    /**
     * A product page with one color shown.
     *
     * @param  iterable<ProductVariant>  $variants  the shown color's sizes
     * @return array<string, mixed>
     */
    public function viewContent(string $productName, iterable $variants, int $price): array
    {
        $skus = collect($variants)->map(fn (ProductVariant $variant): string => $variant->sku)->values()->all();

        return $this->event('ViewContent', $price, array_map(fn (string $sku): array => ['id' => $sku, 'quantity' => 1, 'price' => $price / 100], $skus), ['content_name' => $productName]);
    }

    /**
     * Pieces of one color and size added to the bag.
     *
     * @return array<string, mixed>
     */
    public function addToCart(ProductVariant $variant, int $quantity, int $unitPrice): array
    {
        return $this->event('AddToCart', $unitPrice * $quantity, [['id' => $variant->sku, 'quantity' => $quantity, 'price' => $unitPrice / 100]]);
    }

    /**
     * The checkout page opened.
     *
     * @return array<string, mixed>
     */
    public function initiateCheckout(CartSummary $summary): array
    {
        $contents = $summary->lines
            ->filter(fn (CartLine $line): bool => $line->isOrderable())
            ->map(fn (CartLine $line): array => ['id' => $line->variant->sku, 'quantity' => $line->quantity, 'price' => $line->unitPrice / 100])
            ->values()
            ->all();

        return $this->event('InitiateCheckout', $summary->total(), $contents);
    }

    /**
     * An order placed. The event ID is the same one the server sends.
     *
     * @return array<string, mixed>
     */
    public function purchase(Order $order): array
    {
        $contents = $order->items
            ->map(fn (OrderItem $item): array => ['id' => $item->sku, 'quantity' => $item->quantity, 'price' => $item->unit_price / 100])
            ->values()
            ->all();

        return [...$this->event('Purchase', $order->grand_total, $contents, ['order_id' => $order->number]), 'id' => self::purchaseEventId($order)];
    }

    /**
     * Get the ID that lets ad platforms count a purchase once when both the browser and the server send it.
     */
    public static function purchaseEventId(Order $order): string
    {
        return 'purchase-'.$order->number;
    }

    /**
     * @param  list<array{id: string, quantity: int, price: float}>  $contents
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function event(string $name, int $dirhams, array $contents, array $extra = []): array
    {
        return [
            'event' => $name,
            'id' => Str::lower($name).'-'.Str::uuid(),
            'currency' => 'QAR',
            'value' => $dirhams / 100,
            'contents' => $contents,
            ...$extra,
        ];
    }
}
