<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Delete bags nobody has touched for 30 days (see App\Models\Cart::prunable()).
Schedule::command('model:prune')->daily();

// Morning email of the sizes running low, in the store's time zone (Asia/Qatar).
Schedule::command('shop:send-low-stock-digest')->dailyAt('08:00');
