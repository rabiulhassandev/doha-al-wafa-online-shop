<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ConversionEvents;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends a placed order to the ad platforms from the server, after the shopper allowed ad tracking.
 * Runs on the queue so a slow platform never holds up the thank-you page.
 */
class SendPurchaseToAdPlatforms implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before each retry.
     *
     * @var list<int>
     */
    public array $backoff = [60, 300];

    public function __construct(public Order $order) {}

    public function handle(ConversionEvents $conversionEvents): void
    {
        $conversionEvents->sendPurchase($this->order);
    }
}
