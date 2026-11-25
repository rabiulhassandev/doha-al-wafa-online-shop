<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            ['Black', 'أسود', '#000000'],
            ['White', 'أبيض', '#ffffff'],
            ['Off White', 'أبيض مائل للكريمي', '#faf9f6'],
            ['Ivory', 'عاجي', '#fffff0'],
            ['Cream', 'كريمي', '#fffdd0'],
            ['Beige', 'بيج', '#f5f5dc'],
            ['Nude', 'نود', '#e3bc9a'],
            ['Sand', 'رملي', '#c2b280'],
            ['Camel', 'جملي', '#c19a6b'],
            ['Tan', 'بني فاتح', '#d2b48c'],
            ['Taupe', 'بني رمادي', '#8b8589'],
            ['Brown', 'بني', '#8b4513'],
            ['Chocolate', 'شوكولاتة', '#5c3317'],
            ['Coffee', 'قهوي', '#6f4e37'],
            ['Mocha', 'موكا', '#967969'],
            ['Gray', 'رمادي', '#808080'],
            ['Light Gray', 'رمادي فاتح', '#d3d3d3'],
            ['Charcoal', 'فحمي', '#36454f'],
            ['Silver', 'فضي', '#c0c0c0'],
            ['Gold', 'ذهبي', '#d4af37'],
            ['Champagne', 'شامبين', '#f7e7ce'],
            ['Bronze', 'برونزي', '#cd7f32'],
            ['Rose Gold', 'ذهبي وردي', '#b76e79'],
            ['Red', 'أحمر', '#ff0000'],
            ['Dark Red', 'أحمر داكن', '#8b0000'],
            ['Maroon', 'عنابي غامق', '#800000'],
            ['Burgundy', 'عنابي', '#800020'],
            ['Wine', 'نبيذي', '#722f37'],
            ['Rust', 'صدئي', '#b7410e'],
            ['Terracotta', 'طوبي', '#e2725b'],
            ['Coral', 'مرجاني', '#ff7f50'],
            ['Salmon', 'سلموني', '#fa8072'],
            ['Peach', 'خوخي', '#ffdab9'],
            ['Orange', 'برتقالي', '#ffa500'],
            ['Burnt Orange', 'برتقالي محروق', '#cc5500'],
            ['Apricot', 'مشمشي', '#fbceb1'],
            ['Yellow', 'أصفر', '#ffff00'],
            ['Mustard', 'خردلي', '#e1ad01'],
            ['Lemon', 'ليموني', '#fff44f'],
            ['Pink', 'وردي', '#ffc0cb'],
            ['Baby Pink', 'وردي فاتح', '#f4c2c2'],
            ['Blush', 'وردي خجلي', '#de5d83'],
            ['Dusty Rose', 'وردي مغبر', '#c9a9a6'],
            ['Hot Pink', 'وردي فاقع', '#ff69b4'],
            ['Fuchsia', 'فوشي', '#ff00ff'],
            ['Magenta', 'أرجواني وردي', '#d0006f'],
            ['Purple', 'بنفسجي', '#800080'],
            ['Lavender', 'لافندر', '#b57edc'],
            ['Lilac', 'ليلكي', '#c8a2c8'],
            ['Mauve', 'موف', '#e0b0ff'],
            ['Plum', 'برقوقي', '#673147'],
            ['Eggplant', 'باذنجاني', '#614051'],
            ['Violet', 'بنفسجي زهري', '#8f00ff'],
            ['Blue', 'أزرق', '#0000ff'],
            ['Navy', 'كحلي', '#000080'],
            ['Royal Blue', 'أزرق ملكي', '#4169e1'],
            ['Sky Blue', 'أزرق سماوي', '#87ceeb'],
            ['Baby Blue', 'أزرق فاتح', '#89cff0'],
            ['Denim Blue', 'أزرق جينز', '#1560bd'],
            ['Cobalt Blue', 'أزرق كوبالت', '#0047ab'],
            ['Petrol Blue', 'أزرق بترولي', '#005f6a'],
            ['Green', 'أخضر', '#008000'],
            ['Dark Green', 'أخضر داكن', '#006400'],
            ['Forest Green', 'أخضر غابي', '#228b22'],
            ['Emerald Green', 'أخضر زمردي', '#50c878'],
            ['Olive', 'زيتوني', '#808000'],
            ['Sage', 'أخضر مريمي', '#b2ac88'],
            ['Mint', 'نعناعي', '#98ff98'],
            ['Lime', 'أخضر ليموني', '#bfff00'],
            ['Khaki', 'كاكي', '#c3b091'],
            ['Teal', 'أخضر مزرق', '#008080'],
            ['Turquoise', 'فيروزي', '#40e0d0'],
            ['Aqua', 'مائي', '#00ffff'],
        ];

        foreach ($colors as $index => [$nameEn, $nameAr, $hex]) {
            Color::query()->firstOrCreate(
                ['slug' => Str::slug($nameEn)],
                [
                    'name_en' => $nameEn,
                    'name_ar' => $nameAr,
                    'hex' => $hex,
                    'sort_order' => ($index + 1) * 10,
                ],
            );
        }
    }
}
