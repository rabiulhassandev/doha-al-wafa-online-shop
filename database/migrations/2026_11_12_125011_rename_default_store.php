<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->rename('Doha AL WAFA', 'BANAT AL WAFA');
    }

    public function down(): void
    {
        $this->rename('BANAT AL WAFA', 'Doha AL WAFA');
    }

    private function rename(string $previous, string $name): void
    {
        $setting = DB::table('settings')->where('key', 'store_name');
        $value = $setting->value('value');

        if ($value !== null && json_decode($value) === $previous) {
            $setting->update(['value' => json_encode($name), 'updated_at' => now()]);
            Cache::forget('shop.settings');
        }
    }
};
