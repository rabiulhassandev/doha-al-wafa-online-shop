<?php

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShortLink;
use App\Models\User;

/**
 * Build a complete short link form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function shortLinkPayload(array $overrides = []): array
{
    return [
        'label' => 'Instagram bio',
        'code' => '',
        'destination' => '/ar/products/classic-jelabiya?color=navy',
        'utm_source' => 'Instagram',
        'utm_medium' => 'bio',
        'utm_campaign' => 'eid-2026',
        'utm_content' => '',
        'coupon_code' => '',
        'is_active' => '1',
        ...$overrides,
    ];
}

describe('index', function () {
    it('shows each link with its visits, orders and sales', function () {
        $shortLink = ShortLink::factory()->create(['label' => 'Eid video', 'code' => 'eid-video']);
        $shortLink->forceFill(['clicks' => 40])->save();
        Order::factory()->for($shortLink)->create(['grand_total' => 18500]);
        Order::factory()->for($shortLink)->create(['grand_total' => 21000, 'status' => OrderStatus::Returned]);
        Order::factory()->for($shortLink)->create(['grand_total' => 50000, 'status' => OrderStatus::Cancelled]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.short-links.index'))
            ->assertOk()
            ->assertSee('Eid video')
            ->assertSee(url('p/eid-video'))
            ->assertSeeInOrder(['40', '2', '5.0% of visits', 'QAR 185']);
    });

    it('is closed to customers', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.short-links.index'))
            ->assertForbidden();
    });
});

describe('create', function () {
    it('fills in a product’s page when opened from the product', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.short-links.create', ['label' => 'Classic jelabiya', 'destination' => '/ar/products/classic-jelabiya']))
            ->assertOk()
            ->assertSee('value="Classic jelabiya"', false)
            ->assertSee('value="/ar/products/classic-jelabiya"', false);
    });

    it('offers a short link button on the product pages', function () {
        $product = Product::factory()->create(['slug' => 'classic-jelabiya']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee(e(route('admin.short-links.create', ['label' => $product->name_en, 'destination' => '/ar/products/classic-jelabiya'])), false);
    });
});

describe('store', function () {
    it('makes a link with a random code and lower-case tags', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.short-links.store'), shortLinkPayload())
            ->assertRedirectToRoute('admin.short-links.index');

        $shortLink = ShortLink::query()->sole();
        expect($shortLink->code)->toMatch('/^[a-z2-9]{6}$/');
        expect($shortLink->only(['label', 'destination', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'coupon_code', 'is_active']))->toBe([
            'label' => 'Instagram bio',
            'destination' => '/ar/products/classic-jelabiya?color=navy',
            'utm_source' => 'instagram',
            'utm_medium' => 'bio',
            'utm_campaign' => 'eid-2026',
            'utm_content' => null,
            'coupon_code' => null,
            'is_active' => true,
        ]);
    });

    it('keeps a chosen code and discount code', function () {
        Coupon::factory()->create(['code' => 'SARA10']);

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.short-links.store'), shortLinkPayload(['code' => 'Eid-Sara', 'coupon_code' => 'sara10']));

        expect(ShortLink::query()->sole()->only(['code', 'coupon_code']))->toBe(['code' => 'eid-sara', 'coupon_code' => 'SARA10']);
    });

    it('turns a pasted store address into its page', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.short-links.store'), shortLinkPayload(['destination' => url('/en/shop?category=women')]));

        expect(ShortLink::query()->sole()->destination)->toBe('/en/shop?category=women');
    });

    it('checks the page, the code, the tags and the discount code', function (array $overrides, string $field) {
        ShortLink::factory()->create(['code' => 'taken']);

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.short-links.store'), shortLinkPayload($overrides))
            ->assertSessionHasErrors($field);

        expect(ShortLink::query()->count())->toBe(1);
    })->with([
        'another site' => [['destination' => 'https://example.com/shop'], 'destination'],
        'another site without the scheme' => [['destination' => '//example.com/shop'], 'destination'],
        'a page without a slash' => [['destination' => 'ar/shop'], 'destination'],
        'another short link' => [['destination' => '/p/taken'], 'destination'],
        'no page' => [['destination' => ''], 'destination'],
        'code already used' => [['code' => 'TAKEN'], 'code'],
        'code with spaces' => [['code' => 'eid sara'], 'code'],
        'code too short' => [['code' => 'ab'], 'code'],
        'source with spaces' => [['utm_source' => 'insta gram'], 'utm_source'],
        'unknown discount code' => [['coupon_code' => 'NOPE'], 'coupon_code'],
        'no name' => [['label' => ''], 'label'],
    ]);
});

describe('update', function () {
    it('keeps the code when the field is left empty', function () {
        $shortLink = ShortLink::factory()->create(['code' => 'k7q2mx']);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.short-links.update', $shortLink), shortLinkPayload(['label' => 'Story', 'code' => '', 'is_active' => '0']))
            ->assertRedirectToRoute('admin.short-links.index');

        expect($shortLink->fresh()->only(['code', 'label', 'is_active']))->toBe(['code' => 'k7q2mx', 'label' => 'Story', 'is_active' => false]);
    });

    it('lets a link keep its own code', function () {
        $shortLink = ShortLink::factory()->create(['code' => 'k7q2mx']);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.short-links.update', $shortLink), shortLinkPayload(['code' => 'k7q2mx']))
            ->assertSessionHasNoErrors();
    });
});

describe('destroy', function () {
    it('deletes a link that brought no orders', function () {
        $shortLink = ShortLink::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.short-links.destroy', $shortLink))
            ->assertRedirectToRoute('admin.short-links.index');

        expect($shortLink->fresh())->toBeNull();
    });

    it('keeps a link that brought orders', function () {
        $shortLink = ShortLink::factory()->create(['label' => 'Eid video']);
        Order::factory()->for($shortLink)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.short-links.destroy', $shortLink))
            ->assertSessionHas('error', 'The link “Eid video” brought orders, so it stays for their history. Turn it off instead.');

        expect($shortLink->fresh())->not->toBeNull();
    });
});
