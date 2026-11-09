<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\SizeGuide;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * List products with search and filters.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = ProductStatus::tryFrom((string) $request->query('status'));
        $categoryId = $request->integer('category') ?: null;

        $products = Product::query()
            ->with(['category', 'colors.color', 'colors.images'])
            ->withSum(['variants as stock_total' => fn (Builder $query) => $query->where('is_active', true)], 'stock')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($categoryId !== null, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'search' => $search,
            'status' => $status,
            'categoryId' => $categoryId,
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    /**
     * Show the form for creating a product.
     */
    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Create a product, then continue to its colors.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $product = Product::query()->create($request->productAttributes());

            if ($product->code === null) {
                $product->update(['code' => sprintf('DW%04d', $product->id)]);
            }

            $product->collections()->sync($request->collectionIds());

            return $product;
        });

        return to_route('admin.products.colors.index', $product)
            ->with('status', "“{$product->name_en}” was created. Now add its colors and photos.");
    }

    /**
     * Show the form for editing a product's details.
     */
    public function edit(Product $product): View
    {
        $product->load(['collections', 'colors.color', 'colors.images', 'variants']);

        return view('admin.products.edit', [
            'product' => $product,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update a product's details.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            $product->update($request->productAttributes());
            $product->collections()->sync($request->collectionIds());
        });

        return to_route('admin.products.edit', $product)->with('status', "“{$product->name_en}” was saved.");
    }

    /**
     * Delete a product with its photos, colors and sizes.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('admin.products.index')->with('status', "“{$product->name_en}” was deleted.");
    }

    /**
     * Get the options the product form needs.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categoryOptions' => $this->categoryOptions(),
            'collections' => Collection::query()->ordered()->get(),
            'sizeGuideOptions' => ['' => 'No size guide'] + SizeGuide::query()->orderBy('name')->pluck('name', 'id')->all(),
            'statusOptions' => ProductStatus::options(),
        ];
    }

    /**
     * Get every category as select options, with sub-categories shown under their parent.
     *
     * @return array<int, string>
     */
    private function categoryOptions(): array
    {
        $categories = Category::query()
            ->topLevel()
            ->ordered()
            ->with(['children' => fn ($query) => $query->ordered()])
            ->get();

        $options = [];

        foreach ($categories as $category) {
            $options[$category->id] = $category->name_en;

            foreach ($category->children as $child) {
                $options[$child->id] = "{$category->name_en} › {$child->name_en}";
            }
        }

        return $options;
    }
}
