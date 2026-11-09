<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Mail\LowStockDigest;
use App\Models\ProductVariant;
use App\Services\Settings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('shop:send-low-stock-digest')]
#[Description('Email the store a summary of the colors and sizes on sale that are running low')]
class SendLowStockDigestCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(Settings $settings): int
    {
        $email = $settings->get('order_alert_email');

        if (blank($email)) {
            $this->components->info('No store alerts email is set, so nothing was sent.');

            return self::SUCCESS;
        }

        $threshold = (int) $settings->get('low_stock_threshold');
        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->where('stock', '<=', $threshold)
            ->whereRelation('product', 'status', ProductStatus::Active)
            ->whereRelation('productColor', 'is_active', true)
            ->with(['product', 'productColor.color', 'size'])
            ->orderBy('stock')
            ->orderBy('product_id')
            ->orderBy('id')
            ->get();

        if ($variants->isEmpty()) {
            $this->components->info('Nothing is running low.');

            return self::SUCCESS;
        }

        Mail::to($email)->send(new LowStockDigest($variants, $threshold));

        $this->components->info("Sent the low stock summary of {$variants->count()} colors and sizes to {$email}.");

        return self::SUCCESS;
    }
}
