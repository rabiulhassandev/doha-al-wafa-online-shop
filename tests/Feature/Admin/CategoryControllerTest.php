<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Build a complete category form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function categoryPayload(array $overrides = []): array
{
    return [
        'name_en' => 'Women',
        'name_ar' => 'نساء',
        'slug' => '',
        'parent_id' => '',
        'description_en' => '',
        'description_ar' => '',
        'sort_order' => '10',
        'is_active' => '1',
        ...$overrides,
    ];
}

describe('index', function () {
    it('lists sub-categories under their parent', function () {
        $women = Category::factory()->create(['name_en' => 'Women', 'sort_order' => 1]);
        Category::factory()->childOf($women)->create(['name_en' => 'Kaftans']);
        Category::factory()->create(['name_en' => 'Men', 'sort_order' => 2]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSeeInOrder(['Women', 'Kaftans', 'Men']);
    });

    it('forbids customers', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    });
});

describe('store', function () {
    it('creates a category with a URL name built from the English name', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.categories.store'), categoryPayload(['name_en' => 'Eid Specials']))
            ->assertRedirectToRoute('admin.categories.index')
            ->assertSessionHas('status', '“Eid Specials” was created.');

        $category = Category::query()->sole();
        expect($category->slug)->toBe('eid-specials');
        expect($category->name_ar)->toBe('نساء');
        expect($category->sort_order)->toBe(10);
        expect($category->is_active)->toBeTrue();
    });

    it('adds a number to a generated URL name that is already used', function () {
        Category::factory()->create(['slug' => 'women']);

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.categories.store'), categoryPayload())
            ->assertRedirectToRoute('admin.categories.index');

        $this->assertDatabaseHas('categories', ['slug' => 'women-2']);
    });

    it('tidies a typed URL name', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.categories.store'), categoryPayload(['slug' => 'Women Abayas']))
            ->assertRedirectToRoute('admin.categories.index');

        $this->assertDatabaseHas('categories', ['slug' => 'women-abayas']);
    });

    it('rejects a typed URL name that is already used', function () {
        Category::factory()->create(['slug' => 'women']);

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.categories.store'), categoryPayload(['slug' => 'women']))
            ->assertSessionHasErrors(['slug' => 'The URL name has already been taken.']);
    });

    it('requires names in both languages', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.categories.store'), categoryPayload(['name_en' => '', 'name_ar' => '']))
            ->assertSessionHasErrors([
                'name_en' => 'The English name field is required.',
                'name_ar' => 'The Arabic name field is required.',
            ]);

        $this->assertDatabaseCount('categories', 0);
    });

    it('only accepts a top-level category as the parent', function () {
        $women = Category::factory()->create();
        $kaftans = Category::factory()->childOf($women)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.categories.store'), categoryPayload(['name_en' => 'Embroidered', 'parent_id' => (string) $kaftans->id]))
            ->assertSessionHasErrors(['parent_id' => 'Choose a top-level category as the parent.']);
    });

    it('stores the uploaded image as WebP', function () {
        Storage::fake('public');

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.categories.store'), categoryPayload(['image' => UploadedFile::fake()->image('women.jpg', 1600, 1200)]))
            ->assertRedirectToRoute('admin.categories.index');

        $imagePath = Category::query()->sole()->image_path;
        expect($imagePath)->toStartWith('categories/')->toEndWith('.webp');
        Storage::disk('public')->assertExists($imagePath);
    });

    it('rejects files that are not images', function () {
        Storage::fake('public');

        $this->actingAs(User::factory()->staff()->create())
            ->post(route('admin.categories.store'), categoryPayload(['image' => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf')]))
            ->assertSessionHasErrors(['image' => 'The image field must be an image.']);
    });
});

describe('update', function () {
    it('keeps the URL name when it is unchanged', function () {
        $category = Category::factory()->create(['slug' => 'women']);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.categories.update', $category), categoryPayload(['slug' => 'women', 'is_active' => '0']))
            ->assertRedirectToRoute('admin.categories.index')
            ->assertSessionHasNoErrors();

        $category->refresh();
        expect($category->slug)->toBe('women');
        expect($category->is_active)->toBeFalse();
    });

    it('replaces the image and deletes the old file', function () {
        Storage::fake('public');
        Storage::disk('public')->put('categories/old.webp', 'old image');
        $category = Category::factory()->create(['image_path' => 'categories/old.webp']);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.categories.update', $category), categoryPayload([
                'slug' => $category->slug,
                'image' => UploadedFile::fake()->image('new.jpg', 800, 800),
            ]))
            ->assertRedirectToRoute('admin.categories.index');

        $newPath = $category->refresh()->image_path;
        expect($newPath)->not->toBe('categories/old.webp');
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('categories/old.webp');
    });

    it('removes the image when asked', function () {
        Storage::fake('public');
        Storage::disk('public')->put('categories/old.webp', 'old image');
        $category = Category::factory()->create(['image_path' => 'categories/old.webp']);

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.categories.update', $category), categoryPayload(['slug' => $category->slug, 'remove_image' => '1']))
            ->assertRedirectToRoute('admin.categories.index');

        expect($category->refresh()->image_path)->toBeNull();
        Storage::disk('public')->assertMissing('categories/old.webp');
    });

    it('stops a category from being its own parent', function () {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.categories.update', $category), categoryPayload(['slug' => $category->slug, 'parent_id' => (string) $category->id]))
            ->assertSessionHasErrors(['parent_id' => "A category can't be its own parent."]);
    });

    it('keeps a category with sub-categories at the top level', function () {
        $women = Category::factory()->create();
        Category::factory()->childOf($women)->create();
        $men = Category::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.categories.update', $women), categoryPayload(['slug' => $women->slug, 'parent_id' => (string) $men->id]))
            ->assertSessionHasErrors(['parent_id' => 'This category has sub-categories, so it must stay at the top level.']);

        expect($women->refresh()->parent_id)->toBeNull();
    });
});

describe('destroy', function () {
    it('deletes a category and its image', function () {
        Storage::fake('public');
        Storage::disk('public')->put('categories/women.webp', 'image');
        $category = Category::factory()->create(['name_en' => 'Women', 'image_path' => 'categories/women.webp']);

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirectToRoute('admin.categories.index')
            ->assertSessionHas('status', '“Women” was deleted.');

        $this->assertModelMissing($category);
        Storage::disk('public')->assertMissing('categories/women.webp');
    });

    it('refuses to delete a category that has sub-categories', function () {
        $women = Category::factory()->create(['name_en' => 'Women']);
        Category::factory()->childOf($women)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $women))
            ->assertRedirectToRoute('admin.categories.index')
            ->assertSessionHas('error', '“Women” has sub-categories. Move or delete them first.');

        $this->assertModelExists($women);
    });
});
