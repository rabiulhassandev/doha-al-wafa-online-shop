<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SizeGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SizeRequest;
use App\Models\Size;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SizeController extends Controller
{
    /**
     * List the sizes.
     */
    public function index(): View
    {
        return view('admin.sizes.index', [
            'sizes' => Size::query()->ordered()->paginate(100),
        ]);
    }

    /**
     * Show the form for adding a size.
     */
    public function create(): View
    {
        return view('admin.sizes.create', ['size' => new Size(['group' => SizeGroup::Letter])]);
    }

    /**
     * Add a size.
     */
    public function store(SizeRequest $request): RedirectResponse
    {
        $size = Size::query()->create($request->sizeAttributes());

        return to_route('admin.sizes.index')->with('status', "Size “{$size->name_en}” was added.");
    }

    /**
     * Show the form for editing a size.
     */
    public function edit(Size $size): View
    {
        return view('admin.sizes.edit', ['size' => $size]);
    }

    /**
     * Update a size.
     */
    public function update(SizeRequest $request, Size $size): RedirectResponse
    {
        $size->update($request->sizeAttributes());

        return to_route('admin.sizes.index')->with('status', "Size “{$size->name_en}” was updated.");
    }

    /**
     * Delete a size.
     */
    public function destroy(Size $size): RedirectResponse
    {
        if ($size->variants()->exists()) {
            return back()->with('error', "Size “{$size->name_en}” is used by products. Switch it off in their stock grids instead.");
        }

        $size->delete();

        return to_route('admin.sizes.index')->with('status', "Size “{$size->name_en}” was deleted.");
    }
}
