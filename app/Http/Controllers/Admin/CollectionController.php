<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImagePreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CollectionRequest;
use App\Models\Collection;
use App\Services\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CollectionController extends Controller
{
    /**
     * List the collections.
     */
    public function index(): View
    {
        return view('admin.collections.index', [
            'collections' => Collection::query()->ordered()->paginate(50),
        ]);
    }

    /**
     * Show the form for creating a collection.
     */
    public function create(): View
    {
        return view('admin.collections.create', ['collection' => new Collection]);
    }

    /**
     * Create a collection.
     */
    public function store(CollectionRequest $request, ImageStore $images): RedirectResponse
    {
        $collection = new Collection($request->collectionAttributes());
        $collection->banner_path = $images->resolve(null, $request->file('banner'), false, ImagePreset::CollectionBanner);
        $collection->save();

        return to_route('admin.collections.index')->with('status', "“{$collection->name_en}” was created.");
    }

    /**
     * Show the form for editing a collection.
     */
    public function edit(Collection $collection): View
    {
        return view('admin.collections.edit', ['collection' => $collection]);
    }

    /**
     * Update a collection.
     */
    public function update(CollectionRequest $request, Collection $collection, ImageStore $images): RedirectResponse
    {
        $collection->fill($request->collectionAttributes());
        $collection->banner_path = $images->resolve(
            $collection->banner_path,
            $request->file('banner'),
            $request->boolean('remove_banner'),
            ImagePreset::CollectionBanner,
        );
        $collection->save();

        return to_route('admin.collections.index')->with('status', "“{$collection->name_en}” was updated.");
    }

    /**
     * Delete a collection.
     */
    public function destroy(Collection $collection): RedirectResponse
    {
        $collection->delete();

        return to_route('admin.collections.index')->with('status', "“{$collection->name_en}” was deleted.");
    }
}
