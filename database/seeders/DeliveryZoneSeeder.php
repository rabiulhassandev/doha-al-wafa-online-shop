<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

/**
 * Qatar's eight municipalities as starter delivery areas.
 *
 * The fees and delivery times are placeholders for the owner to replace. Safe to run more than once:
 * existing areas (matched by English name) are left as the owner edited them. Run on a live store with
 * "php artisan db:seed --class=DeliveryZoneSeeder".
 */
class DeliveryZoneSeeder extends Seeder
{
    /**
     * Seed the starter delivery areas.
     */
    public function run(): void
    {
        $zones = [
            ['Doha', 'الدوحة', 20, 1, 2],
            ['Al Rayyan', 'الريان', 20, 1, 2],
            ['Al Wakrah', 'الوكرة', 25, 1, 3],
            ['Al Daayen (Lusail)', 'الضعاين (لوسيل)', 25, 1, 3],
            ['Umm Salal', 'أم صلال', 25, 1, 3],
            ['Al Khor & Al Thakhira', 'الخور والذخيرة', 35, 2, 4],
            ['Al Shahaniya', 'الشحانية', 35, 2, 4],
            ['Al Shamal', 'الشمال', 40, 2, 4],
        ];

        foreach ($zones as $index => [$nameEn, $nameAr, $feeInRiyals, $minDays, $maxDays]) {
            DeliveryZone::query()->firstOrCreate(['name_en' => $nameEn], [
                'name_ar' => $nameAr,
                'fee' => $feeInRiyals * 100,
                'eta_min_days' => $minDays,
                'eta_max_days' => $maxDays,
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }
}
