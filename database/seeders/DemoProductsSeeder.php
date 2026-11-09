<?php

namespace Database\Seeders;

use App\Actions\AdjustStock;
use App\Actions\CreateProductVariant;
use App\Enums\ImageFocus;
use App\Enums\ProductStatus;
use App\Enums\StockMovementReason;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\SizeGuide;
use App\Services\ImageStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample products with placeholder photos, for local development only. Needs CatalogDefaultsSeeder first.
 */
class DemoProductsSeeder extends Seeder
{
    public function __construct(
        private readonly ImageStore $images,
        private readonly CreateProductVariant $createVariant,
        private readonly AdjustStock $adjustStock,
    ) {}

    /**
     * Seed the demo products.
     */
    public function run(): void
    {
        $products = [
            [
                'name_en' => 'Classic crepe jelabiya', 'name_ar' => 'جلابية كريب كلاسيكية', 'category' => 'women',
                'price' => 185, 'compare_at_price' => 220, 'colors' => ['navy', 'olive', 'rose'], 'sizes' => ['52', '54', '56', '58'],
                'collections' => ['new-arrivals', 'best-sellers'], 'featured' => true,
            ],
            [
                'name_en' => 'Embroidered Eid kaftan', 'name_ar' => 'قفطان العيد المطرز', 'category' => 'women',
                'price' => 320, 'compare_at_price' => null, 'colors' => ['maroon', 'emerald', 'ivory'], 'sizes' => ['54', '56', '58', '60'],
                'collections' => ['eid'], 'featured' => true,
            ],
            [
                'name_en' => 'Everyday cotton jelabiya', 'name_ar' => 'جلابية قطن يومية', 'category' => 'men',
                'price' => 145, 'compare_at_price' => null, 'colors' => ['white', 'beige', 'grey'], 'sizes' => ['s', 'm', 'l', 'xl'],
                'collections' => ['new-arrivals'], 'featured' => false,
            ],
            [
                'name_en' => 'Girls festive jelabiya', 'name_ar' => 'جلابية بناتي للمناسبات', 'category' => 'kids',
                'price' => 95, 'compare_at_price' => null, 'colors' => ['lilac', 'gold'], 'sizes' => ['free-size'],
                'collections' => ['eid'], 'featured' => false,
            ],
        ];

        $sizeGuideId = SizeGuide::query()->value('id');

        foreach ($products as $index => $data) {
            if (Product::query()->where('slug', Str::slug($data['name_en']))->exists()) {
                continue;
            }

            $product = Product::query()->create([
                'category_id' => Category::query()->where('slug', $data['category'])->value('id'),
                'size_guide_id' => $data['category'] === 'kids' ? null : $sizeGuideId,
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'slug' => Str::slug($data['name_en']),
                'code' => sprintf('DW%04d', $index + 1),
                'description_en' => 'A flowing jelabiya with a relaxed fit, made for warm days and special evenings.',
                'description_ar' => 'جلابية واسعة ومريحة، مناسبة للأيام الحارة والمناسبات الخاصة.',
                'details_en' => 'Soft crepe. Hand wash cold. Do not tumble dry.',
                'details_ar' => 'كريب ناعم. غسيل يدوي بماء بارد. لا تستخدم المجفف.',
                'price' => $data['price'] * 100,
                'compare_at_price' => $data['compare_at_price'] === null ? null : $data['compare_at_price'] * 100,
                'status' => ProductStatus::Active,
                'is_featured' => $data['featured'],
            ]);
            // Set explicitly: DatabaseSeeder runs without model events, so the publish hook does not fire.
            $product->forceFill(['published_at' => now()->subDays($index)])->save();

            $product->collections()->sync(Collection::query()->whereIn('slug', $data['collections'])->pluck('id'));

            $sizes = Size::query()->whereIn('slug', $data['sizes'])->ordered()->get();

            foreach ($data['colors'] as $position => $colorSlug) {
                $color = Color::query()->where('slug', $colorSlug)->firstOrFail();
                $productColor = $product->colors()->create([
                    'color_id' => $color->id,
                    'sort_order' => ($position + 1) * 10,
                    'is_default' => $position === 0,
                ]);

                foreach ([0, 1] as $photo) {
                    $directory = "products/{$product->id}/".Str::lower(Str::random(24));
                    $photoPath = $this->placeholderPhoto($color->hex, $photo);

                    $productColor->images()->create([
                        'directory' => $directory,
                        'widths' => $this->images->storeProductImage($photoPath, $directory, ImageFocus::Center),
                        'focus' => ImageFocus::Center,
                        'sort_order' => ($photo + 1) * 10,
                    ]);

                    unlink($photoPath);
                }

                foreach ($sizes as $size) {
                    $variant = $this->createVariant->handle($productColor, $size);
                    $this->adjustStock->setTo($variant, fake()->randomElement([0, 1, 2, 4, 6, 9]), StockMovementReason::Recount, note: 'Demo data');
                }
            }
        }
    }

    /**
     * Draw a placeholder garment photo in the given color and return its temporary file path.
     */
    private function placeholderPhoto(string $hex, int $variation): string
    {
        [$red, $green, $blue] = sscanf($hex, '#%02x%02x%02x');
        $canvas = imagecreatetruecolor(1200, 1500);

        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 245, 239, 230));

        $fabric = imagecolorallocate($canvas, $red, $green, $blue);
        $offset = $variation * 60;
        imagefilledpolygon($canvas, [430 + $offset, 220, 770 - $offset, 220, 980, 1380, 220, 1380], $fabric);
        imagefilledellipse($canvas, 600, 230, 260 - $offset, 120, $fabric);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'demo-'.Str::random(16).'.jpg';
        imagejpeg($canvas, $path, 90);
        imagedestroy($canvas);

        return $path;
    }
}
