<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Build a complete product form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productPayload(Category $category, array $overrides = []): array
{
    return [
        'name_en' => 'Classic crepe jelabiya',
        'name_ar' => 'جلابية كريب كلاسيكية',
        'slug' => '',
        'code' => '',
        'category_id' => (string) $category->id,
        'size_guide_id' => '',
        'price' => '185.50',
        'compare_at_price' => '',
        'status' => 'draft',
        'is_featured' => '0',
        'description_en' => '',
        'description_ar' => '',
        'details_en' => '',
        'details_ar' => '',
        'meta_title_en' => '',
        'meta_title_ar' => '',
        'meta_description_en' => '',
        'meta_description_ar' => '',
        ...$overrides,
    ];
}

describe('index', function () {
    it('lists products with their total stock of offered sizes', function () {
        $product = Product::factory()->create(['name_en' => 'Classic crepe jelabiya']);
        $productColor = ProductColor::factory()->for($product)->create();
        ProductVariant::factory()->for($productColor)->for($product)->inStock(4)->create();
        ProductVariant::factory()->for($productColor)->for($product)->inStock(3)->create();
        ProductVariant::factory()->for($productColor)->for($product)->notOffered()->inStock(9)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Classic crepe jelabiya')
            ->assertViewHas('products', fn ($products) => (int) $products->first()->stock_total === 7);
    });

    it('filters by search text, category and status', function () {
        $women = Category::factory()->create();
        Product::factory()->for($women)->active()->create(['name_en' => 'Navy kaftan']);
        Product::factory()->for($women)->create(['name_en' => 'Navy draft']);
        Product::factory()->active()->create(['name_en' => 'Navy elsewhere']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.products.index', ['search' => 'navy', 'category' => $women->id, 'status' => 'active']))
            ->assertOk()
            ->assertSee('Navy kaftan')
            ->assertDontSee('Navy draft')
            ->assertDontSee('Navy elsewhere');
    });
});

describe('store', function () {
    it('creates a draft product with its price in dirhams and continues to colors', function () {
        $category = Category::factory()->create();
        $eid = Collection::factory()->create();

        $response = $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.store'), productPayload($category, ['collection_ids' => [(string) $eid->id]]));

        $product = Product::query()->sole();
        $response->assertRedirectToRoute('admin.products.colors.index', $product);
        expect($product->price)->toBe(18550);
        expect($product->slug)->toBe('classic-crepe-jelabiya');
        expect($product->code)->toBe(sprintf('DW%04d', $product->id));
        expect($product->status)->toBe(ProductStatus::Draft);
        expect($product->published_at)->toBeNull();
        expect($product->collections->modelKeys())->toBe([$eid->id]);
    });

    it('keeps a typed product code in capitals', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.store'), productPayload(Category::factory()->create(), ['code' => 'kft01']))
            ->assertSessionHasNoErrors();

        expect(Product::query()->sole()->code)->toBe('KFT01');
    });

    it('rejects an original price that is not higher than the selling price', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.store'), productPayload(Category::factory()->create(), ['price' => '185', 'compare_at_price' => '150']))
            ->assertSessionHasErrors(['compare_at_price' => 'The original price must be higher than the selling price.']);

        $this->assertDatabaseCount('products', 0);
    });

    it('rejects prices with more than two decimals', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.store'), productPayload(Category::factory()->create(), ['price' => '185.555']))
            ->assertSessionHasErrors(['price' => 'The price field must have 0-2 decimal places.']);
    });

    it('requires names, a category and a price', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.store'), ['status' => 'draft'])
            ->assertSessionHasErrors(['name_en', 'name_ar', 'category_id', 'price']);
    });

    it('rejects a product code with symbols', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.store'), productPayload(Category::factory()->create(), ['code' => 'DW-01']))
            ->assertSessionHasErrors(['code' => 'The product code may only contain 2 to 12 letters and numbers.']);
    });
});

describe('update', function () {
    it('stamps the publish date the first time a product becomes active', function () {
        $this->travelTo('2026-11-09 10:30:00');
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.products.update', $product), productPayload($product->category, [
                'slug' => $product->slug,
                'code' => $product->code,
                'status' => 'active',
            ]))
            ->assertRedirectToRoute('admin.products.edit', $product);

        $product->refresh();
        expect($product->status)->toBe(ProductStatus::Active);
        expect($product->published_at->toDateTimeString())->toBe('2026-11-09 10:30:00');
    });

    it('replaces the collections', function () {
        $product = Product::factory()->create();
        $product->collections()->attach(Collection::factory()->create());
        $eid = Collection::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.products.update', $product), productPayload($product->category, [
                'slug' => $product->slug,
                'code' => $product->code,
                'collection_ids' => [(string) $eid->id],
            ]));

        expect($product->collections()->pluck('collections.id')->all())->toBe([$eid->id]);
    });
});

describe('destroy', function () {
    it('deletes the product with its colors, sizes and photo files', function () {
        Storage::fake('public');
        $product = Product::factory()->create();
        $productColor = ProductColor::factory()->for($product)->create();
        $image = ProductImage::factory()->for($productColor)->create(['directory' => 'products/1/abc']);
        Storage::disk('public')->put('products/1/abc/800.webp', 'photo');
        $variant = ProductVariant::factory()->for($productColor)->for($product)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirectToRoute('admin.products.index');

        $this->assertModelMissing($product);
        $this->assertModelMissing($productColor);
        $this->assertModelMissing($image);
        $this->assertModelMissing($variant);
        Storage::disk('public')->assertMissing('products/1/abc/800.webp');
    });
});

it('renders the details form for a product with colors', function () {
    $product = Product::factory()->create(['name_ar' => 'جلابية كريب']);
    ProductColor::factory()->for($product)->create();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertSee('جلابية كريب')
        ->assertSee('Choose sizes and enter stock.');
});
