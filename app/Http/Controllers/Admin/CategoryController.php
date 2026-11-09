<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImagePreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    /**
     * List categories with their sub-categories underneath.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->topLevel()
            ->ordered()
            ->with(['children' => fn ($query) => $query->ordered()])
            ->get();

        return view('admin.categories.index', ['categories' => $categories]);
    }

    /**
     * Show the form for creating a category.
     */
    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category,
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    /**
     * Create a category.
     */
    public function store(CategoryRequest $request, ImageStore $images): RedirectResponse
    {
        $category = new Category($request->categoryAttributes());
        $category->image_path = $images->resolve(null, $request->file('image'), false, ImagePreset::CategoryImage);
        $category->save();

        return to_route('admin.categories.index')->with('status', "“{$category->name_en}” was created.");
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($category),
        ]);
    }

    /**
     * Update a category.
     */
    public function update(CategoryRequest $request, Category $category, ImageStore $images): RedirectResponse
    {
        $category->fill($request->categoryAttributes());
        $category->image_path = $images->resolve(
            $category->image_path,
            $request->file('image'),
            $request->boolean('remove_image'),
            ImagePreset::CategoryImage,
        );
        $category->save();

        return to_route('admin.categories.index')->with('status', "“{$category->name_en}” was updated.");
    }

    /**
     * Delete a category that has no sub-categories.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('error', "“{$category->name_en}” has sub-categories. Move or delete them first.");
        }

        if ($category->products()->exists()) {
            return back()->with('error', "“{$category->name_en}” has products. Move them to another category first.");
        }

        $category->delete();

        return to_route('admin.categories.index')->with('status', "“{$category->name_en}” was deleted.");
    }

    /**
     * Get the categories that may be chosen as a parent.
     *
     * @return array<int|string, string>
     */
    private function parentOptions(?Category $except = null): array
    {
        return ['' => 'None (top level)'] + Category::query()
            ->topLevel()
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->id))
            ->ordered()
            ->pluck('name_en', 'id')
            ->all();
    }
}
