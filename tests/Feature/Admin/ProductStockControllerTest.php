<?php

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;

describe('edit', function () {
    it('shows the grid with the sizes the product sells ticked', function () {
        $variant = ProductVariant::factory()->inStock(7)->create();
        $unusedSize = Size::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.products.stock.edit', $variant->product))
            ->assertOk()
            ->assertViewHas('offeredSizeIds', [$variant->size_id])
            ->assertSee('name="cells['.$variant->product_color_id.']['.$variant->size_id.'][stock]"', false)
            ->assertSee('value="7"', false)
            ->assertSee('name="cells['.$variant->product_color_id.']['.$unusedSize->id.'][stock]"', false);
    });

    it('asks for colors before sizes', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.products.stock.edit', Product::factory()->create()))
            ->assertOk()
            ->assertSee('Add at least one color before choosing sizes.');
    });
});

describe('update', function () {
    it('saves a 3 color × 4 size grid', function () {
        $product = Product::factory()->create(['code' => 'DW0003']);
        $productColors = collect(['navy', 'olive', 'rose'])->map(
            fn (string $slug): ProductColor => ProductColor::factory()->for($product)->for(Color::factory()->create(['slug' => $slug]))->create(),
        );
        $sizes = collect(['52', '54', '56', '58'])->map(fn (string $slug): Size => Size::factory()->create(['slug' => $slug]));

        $cells = [];
        foreach ($productColors as $colorIndex => $productColor) {
            foreach ($sizes as $sizeIndex => $size) {
                $cells[$productColor->id][$size->id] = ['stock' => (string) ($colorIndex + $sizeIndex), 'original_stock' => '0', 'offered' => '1'];
            }
        }

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.products.stock.update', $product), [
                'size_ids' => $sizes->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
                'cells' => $cells,
            ])
            ->assertRedirectToRoute('admin.products.stock.edit', $product)
            ->assertSessionHas('status', 'Sizes and stock were saved.');

        $this->assertDatabaseCount('product_variants', 12);
        expect((int) ProductVariant::query()->sum('stock'))->toBe(30);
        expect(ProductVariant::query()->where('sku', 'DW0003-ROSE-58')->value('stock'))->toBe(5);
    });

    it('rejects negative stock', function () {
        $variant = ProductVariant::factory()->inStock(2)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.products.stock.update', $variant->product), [
                'size_ids' => [(string) $variant->size_id],
                'cells' => [$variant->product_color_id => [$variant->size_id => ['stock' => '-1', 'original_stock' => '2', 'offered' => '1']]],
            ])
            ->assertSessionHasErrors(["cells.{$variant->product_color_id}.{$variant->size_id}.stock" => 'Stock cannot be negative.']);

        expect($variant->refresh()->stock)->toBe(2);
    });

    it('rejects cells for a color of another product', function () {
        $product = Product::factory()->create();
        ProductColor::factory()->for($product)->create();
        $otherProductColor = ProductColor::factory()->create();
        $size = Size::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.products.stock.update', $product), [
                'size_ids' => [(string) $size->id],
                'cells' => [$otherProductColor->id => [$size->id => ['stock' => '5', 'original_stock' => '0', 'offered' => '1']]],
            ])
            ->assertSessionHasErrors(['cells' => 'The colors on this page changed. Reload the page and enter the stock again.']);

        $this->assertDatabaseCount('product_variants', 0);
    });

    it('forbids customers', function () {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.products.stock.update', $product), ['size_ids' => []])
            ->assertForbidden();
    });
});
