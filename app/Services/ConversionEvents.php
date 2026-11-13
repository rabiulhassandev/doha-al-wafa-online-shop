<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\QatarPhone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Sends placed orders to Meta (Conversions API) and TikTok (Events API) from the server, so purchases
 * still count when a browser blocks the pixel. The phone number and email are hashed (SHA-256) before
 * they leave the store, and the event ID matches the browser's so each purchase is counted once.
 */
class ConversionEvents
{
    private const META_API = 'https://graph.facebook.com/v21.0';

    private const TIKTOK_API = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';

    public function __construct(private readonly Settings $settings) {}

    /**
     * Determine whether any platform has both a pixel and an access token.
     */
    public function isConfigured(): bool
    {
        return $this->metaIsConfigured() || $this->tikTokIsConfigured();
    }

    /**
     * Send a placed order to every platform that is set up.
     *
     * @throws RuntimeException when a platform rejects the event, so the queued job tries again
     */
    public function sendPurchase(Order $order): void
    {
        $order->loadMissing('items');

        if ($this->metaIsConfigured()) {
            $this->sendToMeta($order);
        }

        if ($this->tikTokIsConfigured()) {
            $this->sendToTikTok($order);
        }
    }

    private function metaIsConfigured(): bool
    {
        return filled($this->settings->get('meta_pixel_id')) && filled($this->settings->secret('meta_capi_token'));
    }

    private function tikTokIsConfigured(): bool
    {
        return filled($this->settings->get('tiktok_pixel_id')) && filled($this->settings->secret('tiktok_events_token'));
    }

    private function sendToMeta(Order $order): void
    {
        $ids = (array) $order->tracking_ids;
        $phone = QatarPhone::tryParse($order->customer_phone);

        $payload = array_filter([
            'data' => [[
                'event_name' => 'Purchase',
                'event_time' => $order->placed_at->getTimestamp(),
                'event_id' => Tracking::purchaseEventId($order),
                'action_source' => 'website',
                'event_source_url' => $order->landing_url ?? route('home', ['locale' => $order->locale]),
                'user_data' => array_filter([
                    'ph' => $phone ? [self::hash($phone->whatsAppNumber())] : null,
                    'em' => $order->customer_email ? [self::hash($order->customer_email)] : null,
                    'country' => [self::hash('qa')],
                    'client_ip_address' => $order->ip_address,
                    'client_user_agent' => $order->user_agent,
                    'fbp' => $ids['fbp'] ?? null,
                    'fbc' => $ids['fbc'] ?? ($order->click_id_type === 'meta' ? 'fb.1.'.$order->placed_at->getTimestampMs().'.'.$order->click_id : null),
                ]),
                'custom_data' => [
                    'currency' => 'QAR',
                    'value' => $order->grand_total / 100,
                    'order_id' => $order->number,
                    'content_type' => 'product',
                    'content_ids' => $order->items->pluck('sku')->values()->all(),
                    'contents' => $order->items->map(fn (OrderItem $item): array => [
                        'id' => $item->sku,
                        'quantity' => $item->quantity,
                        'item_price' => $item->unit_price / 100,
                    ])->values()->all(),
                ],
            ]],
            'test_event_code' => $this->settings->get('meta_test_event_code') ?: null,
        ]);

        $this->post(
            'Meta',
            self::META_API.'/'.$this->settings->get('meta_pixel_id').'/events?access_token='.urlencode((string) $this->settings->secret('meta_capi_token')),
            $payload,
        );
    }

    private function sendToTikTok(Order $order): void
    {
        $ids = (array) $order->tracking_ids;

        $payload = array_filter([
            'event_source' => 'web',
            'event_source_id' => $this->settings->get('tiktok_pixel_id'),
            'test_event_code' => $this->settings->get('tiktok_test_event_code') ?: null,
            'data' => [[
                'event' => 'PlaceAnOrder',
                'event_time' => $order->placed_at->getTimestamp(),
                'event_id' => Tracking::purchaseEventId($order),
                'user' => array_filter([
                    'phone' => self::hash($order->customer_phone),
                    'email' => $order->customer_email ? self::hash($order->customer_email) : null,
                    'ip' => $order->ip_address,
                    'user_agent' => $order->user_agent,
                    'ttp' => $ids['ttp'] ?? null,
                    'ttclid' => $order->click_id_type === 'tiktok' ? $order->click_id : null,
                ]),
                'properties' => [
                    'currency' => 'QAR',
                    'value' => $order->grand_total / 100,
                    'order_id' => $order->number,
                    'content_type' => 'product',
                    'contents' => $order->items->map(fn (OrderItem $item): array => [
                        'content_id' => $item->sku,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price / 100,
                    ])->values()->all(),
                ],
                'page' => ['url' => $order->landing_url ?? route('home', ['locale' => $order->locale])],
            ]],
        ]);

        $this->post('TikTok', self::TIKTOK_API, $payload, ['Access-Token' => (string) $this->settings->secret('tiktok_events_token')]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     *
     * @throws RuntimeException
     */
    private function post(string $platform, string $url, array $payload, array $headers = []): void
    {
        try {
            $response = Http::timeout(10)->withHeaders($headers)->acceptJson()->asJson()->post($url, $payload);
        } catch (ConnectionException) {
            // Transport errors can include the full URL, which may contain an access token.
            throw new RuntimeException("Could not connect to {$platform} to send a purchase event.");
        }

        if ($response->failed()) {
            Log::warning("{$platform} rejected a purchase event.", ['status' => $response->status()]);

            throw new RuntimeException("{$platform} rejected a purchase event (HTTP {$response->status()}).");
        }
    }

    /**
     * Hash a phone number or email the way ad platforms expect: trimmed, lower case, SHA-256.
     */
    private static function hash(string $value): string
    {
        return hash('sha256', strtolower(trim($value)));
    }
}
