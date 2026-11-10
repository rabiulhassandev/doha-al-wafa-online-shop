<?php

use App\Jobs\SendPurchaseToAdPlatforms;
use App\Models\Order;
use App\Services\ConversionEvents;
use App\Services\Settings;
use App\Support\Attribution;
use App\Support\QatarPhone;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function connectAdPlatforms(bool $meta = true, bool $tikTok = true): void
{
    app(Settings::class)->set(array_filter([
        'meta_pixel_id' => $meta ? '1234567890123456' : null,
        'meta_capi_token' => $meta ? Settings::encryptSecret('meta-token') : null,
        'tiktok_pixel_id' => $tikTok ? 'C1A2B3C4D5E6F7G8H9I0' : null,
        'tiktok_events_token' => $tikTok ? Settings::encryptSecret('tiktok-token') : null,
    ]));
}

/**
 * Place an order for one QAR 185 piece from a shopper who allowed ad tracking, came from a Meta ad and has the pixels' cookies.
 */
function orderFromAdClick(): Order
{
    $variant = listedProduct(['price' => 18500])->variants()->sole();

    return placeOrder([$variant->id => 1], checkoutDetails([
        'email' => 'Fatima@Example.com',
        'attribution' => new Attribution(utm: ['utm_source' => 'facebook'], clickIdType: 'meta', clickId: 'IwAR0abc', landingUrl: 'http://localhost/en?fbclid=IwAR0abc'),
        'adTrackingAllowed' => true,
        'trackingIds' => ['fbp' => 'fb.1.1700000000000.123', 'ttp' => 'ttp-cookie'],
    ]));
}

describe('sending', function () {
    beforeEach(fn () => Queue::fake());

    it('sends the purchase to Meta with hashed contact details and the browser’s event ID', function () {
        connectAdPlatforms(tikTok: false);
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        $order = orderFromAdClick();

        app(ConversionEvents::class)->sendPurchase($order);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($order): bool {
            $event = $request['data'][0];

            return str_starts_with($request->url(), 'https://graph.facebook.com/v21.0/1234567890123456/events?access_token=meta-token')
                && $event['event_name'] === 'Purchase'
                && $event['event_id'] === 'purchase-'.$order->number
                && $event['action_source'] === 'website'
                && $event['user_data']['ph'] === [hash('sha256', '97455551234')]
                && $event['user_data']['em'] === [hash('sha256', 'fatima@example.com')]
                && $event['user_data']['fbp'] === 'fb.1.1700000000000.123'
                && str_ends_with($event['user_data']['fbc'], '.IwAR0abc')
                && $event['custom_data']['value'] == $order->grand_total / 100
                && $event['custom_data']['currency'] === 'QAR'
                && $event['custom_data']['content_ids'] === [$order->items->sole()->sku]
                && ! isset($request['test_event_code']);
        });
    });

    it('sends the purchase to TikTok as a placed order', function () {
        connectAdPlatforms(meta: false);
        app(Settings::class)->set(['tiktok_test_event_code' => 'TEST123']);
        Http::fake(['business-api.tiktok.com/*' => Http::response(['code' => 0])]);
        $order = orderFromAdClick();

        app(ConversionEvents::class)->sendPurchase($order);

        Http::assertSent(function (Request $request) use ($order): bool {
            $event = $request['data'][0];

            return $request->hasHeader('Access-Token', 'tiktok-token')
                && $request['event_source_id'] === 'C1A2B3C4D5E6F7G8H9I0'
                && $request['test_event_code'] === 'TEST123'
                && $event['event'] === 'PlaceAnOrder'
                && $event['event_id'] === 'purchase-'.$order->number
                && $event['user']['phone'] === hash('sha256', QatarPhone::tryParse('5555 1234')->e164())
                && $event['user']['ttp'] === 'ttp-cookie'
                && $event['properties']['contents'][0]['content_id'] === $order->items->sole()->sku;
        });
    });

    it('fails so the job tries again when a platform rejects the event', function () {
        connectAdPlatforms(tikTok: false);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid token']], 400)]);

        app(ConversionEvents::class)->sendPurchase(orderFromAdClick());
    })->throws(RequestException::class);
});

describe('queueing', function () {
    beforeEach(fn () => Queue::fake());

    it('queues the purchase when the shopper allowed ad tracking and a platform is connected', function () {
        connectAdPlatforms();

        $order = orderFromAdClick();

        Queue::assertPushed(SendPurchaseToAdPlatforms::class, fn (SendPurchaseToAdPlatforms $job): bool => $job->order->is($order));
    });

    it('sends nothing for shoppers who refused ad tracking', function () {
        connectAdPlatforms();
        $variant = listedProduct()->variants()->sole();

        placeOrder([$variant->id => 1], checkoutDetails(['adTrackingAllowed' => false]));

        Queue::assertNotPushed(SendPurchaseToAdPlatforms::class);
    });

    it('sends nothing when no platform has an access token', function () {
        app(Settings::class)->set(['meta_pixel_id' => '1234567890123456']);

        orderFromAdClick();

        Queue::assertNotPushed(SendPurchaseToAdPlatforms::class);
    });
});

it('ignores a token that can no longer be read', function () {
    app(Settings::class)->set(['meta_pixel_id' => '1234567890123456', 'meta_capi_token' => 'not-encrypted']);

    expect(app(ConversionEvents::class)->isConfigured())->toBeFalse();
});
