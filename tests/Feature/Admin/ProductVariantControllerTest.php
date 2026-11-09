<?php

use App\Models\ProductVariant;
use App\Models\User;

it('saves SKUs in capitals and size prices in dirhams', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'DW0001-NAVY-56']);

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.products.variants.update', $variant->product), [
            'variants' => [$variant->id => ['sku' => 'dw0001-navy-56-gold', 'price' => '199.50']],
        ])
        ->assertRedirect(route('admin.products.stock.edit', $variant->product).'#skus-and-prices')
        ->assertSessionHas('status', 'SKUs and prices were saved.');

    $variant->refresh();
    expect($variant->sku)->toBe('DW0001-NAVY-56-GOLD');
    expect($variant->price)->toBe(19950);
    expect($variant->effectivePrice())->toBe(19950);
});

it('falls back to the product price when the size price is cleared', function () {
    $variant = ProductVariant::factory()->create(['price' => 19950]);

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.products.variants.update', $variant->product), [
            'variants' => [$variant->id => ['sku' => $variant->sku, 'price' => '']],
        ]);

    $variant->refresh();
    expect($variant->price)->toBeNull();
    expect($variant->effectivePrice())->toBe($variant->product->price);
});

it('rejects a SKU used by another product', function () {
    ProductVariant::factory()->create(['sku' => 'TAKEN-1']);
    $variant = ProductVariant::factory()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.products.variants.update', $variant->product), [
            'variants' => [$variant->id => ['sku' => 'taken-1', 'price' => '']],
        ])
        ->assertSessionHasErrors(["variants.{$variant->id}.sku" => 'The SKU TAKEN-1 is already used by another product.']);
});

it('rejects a variant of another product', function () {
    $variant = ProductVariant::factory()->create();
    $otherVariant = ProductVariant::factory()->create(['sku' => 'OTHER-1']);

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.products.variants.update', $variant->product), [
            'variants' => [$otherVariant->id => ['sku' => 'HIJACKED', 'price' => '']],
        ])
        ->assertSessionHasErrors(['variants' => 'The sizes on this page changed. Reload the page and try again.']);

    expect($otherVariant->refresh()->sku)->toBe('OTHER-1');
});
