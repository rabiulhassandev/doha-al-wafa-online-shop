<?php

use App\Mail\LowStockDigest;
use App\Services\Settings;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

it('emails the colors and sizes on sale that are running low, sold out first', function () {
    app(Settings::class)->set(['order_alert_email' => 'orders@example.com', 'low_stock_threshold' => 3]);
    $low = listedProduct(['name_en' => 'Low'], ['navy' => ['56' => 2]])->variants()->sole();
    $soldOut = listedProduct(['name_en' => 'Sold out'], ['olive' => ['58' => 0]])->variants()->sole();
    listedProduct(['name_en' => 'Plenty'], ['navy' => ['54' => 10]]);
    listedProduct(['name_en' => 'Draft', 'status' => 'draft'], ['navy' => ['52' => 0]]);

    $this->artisan('shop:send-low-stock-digest')->assertSuccessful();

    Mail::assertSent(LowStockDigest::class, function (LowStockDigest $mail) use ($low, $soldOut): bool {
        $mail->assertHasSubject('Stock check: 1 sold out, 1 running low');
        $mail->assertSeeInOrderInHtml(['Sold out', $soldOut->sku, 'Low', $low->sku, '2 left']);

        return $mail->hasTo('orders@example.com') && $mail->variants->modelKeys() === [$soldOut->id, $low->id];
    });
});

it('sends nothing when stock is fine or no email is set', function (array $settings) {
    app(Settings::class)->set($settings);
    listedProduct(stockByColor: ['navy' => ['56' => 10]]);

    $this->artisan('shop:send-low-stock-digest')->assertSuccessful();

    Mail::assertNothingSent();
})->with([
    'stock is fine' => [['order_alert_email' => 'orders@example.com']],
    'no email set' => [['order_alert_email' => null, 'low_stock_threshold' => 50]],
]);

it('runs every morning', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('shop:send-low-stock-digest')->assertSuccessful();
});
