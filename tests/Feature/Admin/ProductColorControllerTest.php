<?php

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

describe('index', function () {
    it('offers only palette colors the product does not have yet', function () {
        $product = Product::factory()->create();
        ProductColor::factory()->for($product)->for(Color::factory()->create(['name_en' => 'Navy']))->create();
        Color::factory()->create(['name_en' => 'Olive']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.products.colors.index', $product))
            ->assertOk()
            ->assertViewHas('availableColors', fn ($colors) => $colors->pluck('name_en')->all() === ['Olive']);
    });
});

describe('store', function () {
    it('makes the first color the one shown first', function () {
        $product = Product::factory()->create();
        $navy = Color::factory()->create(['name_en' => 'Navy']);

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.colors.store', $product), ['color_id' => (string) $navy->id])
            ->assertSessionHas('status', 'Navy was added. Upload its photos below.');

        $productColor = ProductColor::query()->sole();
        expect($productColor->color_id)->toBe($navy->id);
        expect($productColor->is_default)->toBeTrue();
    });

    it('adds the sizes the product already sells to a new color', function () {
        $product = Product::factory()->create(['code' => 'DW0001']);
        $navy = ProductColor::factory()->for($product)->shownFirst()->create();
        $size56 = Size::factory()->create(['slug' => '56']);
        ProductVariant::factory()->for($navy)->for($product)->for($size56)->create();
        ProductVariant::factory()->for($navy)->for($product)->notOffered()->create();
        $olive = Color::factory()->create(['slug' => 'olive']);

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.colors.store', $product), ['color_id' => (string) $olive->id]);

        $oliveColor = ProductColor::query()->where('color_id', $olive->id)->sole();
        expect($oliveColor->is_default)->toBeFalse();
        expect($oliveColor->variants()->pluck('sku')->all())->toBe(['DW0001-OLIVE-56']);
    });

    it('rejects a color the product already has', function () {
        $productColor = ProductColor::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.products.colors.store', $productColor->product), ['color_id' => (string) $productColor->color_id])
            ->assertSessionHasErrors(['color_id' => 'This product already has that color.']);

        $this->assertDatabaseCount('product_colors', 1);
    });
});

describe('update', function () {
    it('saves a custom name and hides the color', function () {
        $productColor = ProductColor::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.products.colors.update', [$productColor->product, $productColor]), [
                'label_en' => 'Navy with gold embroidery',
                'label_ar' => 'كحلي مع تطريز ذهبي',
                'is_active' => '0',
            ]);

        $productColor->refresh();
        expect($productColor->label_en)->toBe('Navy with gold embroidery');
        expect($productColor->is_active)->toBeFalse();
    });

    it('returns 404 for a color that belongs to another product', function () {
        $productColor = ProductColor::factory()->create();
        $otherProduct = Product::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.products.colors.update', [$otherProduct, $productColor]), ['is_active' => '1'])
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('removes the color with its photos and sizes and moves "shown first" to the next color', function () {
        Storage::fake('public');
        $product = Product::factory()->create();
        $navy = ProductColor::factory()->for($product)->shownFirst()->create(['sort_order' => 10]);
        $olive = ProductColor::factory()->for($product)->create(['sort_order' => 20]);
        $image = ProductImage::factory()->for($navy)->create(['directory' => 'products/1/navy']);
        Storage::disk('public')->put('products/1/navy/400.webp', 'photo');
        $variant = ProductVariant::factory()->for($navy)->for($product)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.products.colors.destroy', [$product, $navy]))
            ->assertRedirectToRoute('admin.products.colors.index', $product);

        $this->assertModelMissing($navy);
        $this->assertModelMissing($image);
        $this->assertModelMissing($variant);
        Storage::disk('public')->assertMissing('products/1/navy/400.webp');
        expect($olive->refresh()->is_default)->toBeTrue();
    });
});

it('makes another color the one shown first', function () {
    $product = Product::factory()->create();
    $navy = ProductColor::factory()->for($product)->shownFirst()->create();
    $olive = ProductColor::factory()->for($product)->create();

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.products.colors.default', [$product, $olive]));

    expect($olive->refresh()->is_default)->toBeTrue();
    expect($navy->refresh()->is_default)->toBeFalse();
});

it('moves a color up the list', function () {
    $product = Product::factory()->create();
    $navy = ProductColor::factory()->for($product)->create(['sort_order' => 10]);
    $olive = ProductColor::factory()->for($product)->create(['sort_order' => 20]);

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.products.colors.position', [$product, $olive]), ['direction' => 'up']);

    expect($product->colors()->pluck('id')->all())->toBe([$olive->id, $navy->id]);
});

it('rejects an unknown move direction', function () {
    $productColor = ProductColor::factory()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.products.colors.position', [$productColor->product, $productColor]), ['direction' => 'sideways'])
        ->assertSessionHasErrors(['direction' => 'The selected direction is invalid.']);
});
