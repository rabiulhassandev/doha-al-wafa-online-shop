<?php

namespace Database\Seeders;

use App\Enums\SizeGroup;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Size;
use App\Models\SizeGuide;
use Illuminate\Database\Seeder;

/**
 * Starter catalog data the owner can edit: categories, collections, sizes and a sample size guide.
 *
 * Safe to run more than once: existing rows (matched by URL name) are left as the owner edited them. Run on a live store with
 * "php artisan db:seed --class=CatalogDefaultsSeeder".
 */
class CatalogDefaultsSeeder extends Seeder
{
    /**
     * Seed the starter catalog data.
     */
    public function run(): void
    {
        $this->seedCategories();
        $this->seedCollections();
        $this->seedSizes();
        $this->seedSizeGuide();
    }

    private function seedCategories(): void
    {
        $categories = [
            ['slug' => 'women', 'name_en' => 'Women', 'name_ar' => 'نساء'],
            ['slug' => 'kids', 'name_en' => 'Kids', 'name_ar' => 'أطفال'],
        ];

        foreach ($categories as $index => $category) {
            Category::query()->firstOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'sort_order' => ($index + 1) * 10],
            );
        }
    }

    private function seedCollections(): void
    {
        $collections = [
            ['slug' => 'new-arrivals', 'name_en' => 'New arrivals', 'name_ar' => 'وصل حديثاً'],
            ['slug' => 'best-sellers', 'name_en' => 'Best sellers', 'name_ar' => 'الأكثر مبيعاً'],
            ['slug' => 'eid', 'name_en' => 'Eid collection', 'name_ar' => 'مجموعة العيد'],
        ];

        foreach ($collections as $index => $collection) {
            Collection::query()->firstOrCreate(
                ['slug' => $collection['slug']],
                [...$collection, 'sort_order' => ($index + 1) * 10],
            );
        }
    }

    private function seedSizes(): void
    {
        $sizes = [
            ...array_map(fn (string $name): array => [$name, null, SizeGroup::Letter], ['S', 'M', 'L', 'XL', 'XXL']),
            ...array_map(fn (int $length): array => [(string) $length, null, SizeGroup::Length], range(50, 62, 2)),
            ['Free size', 'مقاس واحد', SizeGroup::OneSize],
        ];

        foreach ($sizes as $index => [$nameEn, $nameAr, $group]) {
            Size::query()->firstOrCreate(
                ['slug' => str($nameEn)->slug()->toString()],
                ['name_en' => $nameEn, 'name_ar' => $nameAr, 'group' => $group, 'sort_order' => ($index + 1) * 10],
            );
        }
    }

    private function seedSizeGuide(): void
    {
        SizeGuide::query()->firstOrCreate(
            ['name' => 'Sample: jelabiya by length (replace with your measurements)'],
            [
                'columns' => [
                    ['en' => 'Length', 'ar' => 'الطول'],
                    ['en' => 'Chest', 'ar' => 'الصدر'],
                    ['en' => 'Sleeve', 'ar' => 'الكم'],
                ],
                'rows' => [
                    ['size' => '52', 'values' => [132.0, 108.0, 56.0]],
                    ['size' => '54', 'values' => [137.0, 112.0, 57.0]],
                    ['size' => '56', 'values' => [142.0, 116.0, 58.0]],
                    ['size' => '58', 'values' => [147.0, 120.0, 59.0]],
                    ['size' => '60', 'values' => [152.0, 124.0, 60.0]],
                ],
                'notes_en' => 'Length is measured from the shoulder to the hem.',
                'notes_ar' => 'يُقاس الطول من الكتف إلى أسفل الجلابية.',
            ],
        );
    }
}
