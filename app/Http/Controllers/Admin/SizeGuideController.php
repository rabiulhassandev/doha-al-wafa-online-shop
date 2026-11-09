<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SizeGuideRequest;
use App\Models\SizeGuide;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SizeGuideController extends Controller
{
    /**
     * List the size guides.
     */
    public function index(): View
    {
        return view('admin.size-guides.index', [
            'sizeGuides' => SizeGuide::query()->orderBy('name')->orderBy('id')->paginate(50),
        ]);
    }

    /**
     * Show the form for creating a size guide.
     */
    public function create(): View
    {
        return view('admin.size-guides.create', [
            'sizeGuide' => new SizeGuide([
                'columns' => [['en' => 'Length', 'ar' => 'الطول']],
                'rows' => [['size' => '', 'values' => [null]]],
            ]),
        ]);
    }

    /**
     * Create a size guide.
     */
    public function store(SizeGuideRequest $request): RedirectResponse
    {
        $sizeGuide = SizeGuide::query()->create($request->sizeGuideAttributes());

        return to_route('admin.size-guides.index')->with('status', "“{$sizeGuide->name}” was created.");
    }

    /**
     * Show the form for editing a size guide.
     */
    public function edit(SizeGuide $sizeGuide): View
    {
        return view('admin.size-guides.edit', ['sizeGuide' => $sizeGuide]);
    }

    /**
     * Update a size guide.
     */
    public function update(SizeGuideRequest $request, SizeGuide $sizeGuide): RedirectResponse
    {
        $sizeGuide->update($request->sizeGuideAttributes());

        return to_route('admin.size-guides.index')->with('status', "“{$sizeGuide->name}” was updated.");
    }

    /**
     * Delete a size guide.
     */
    public function destroy(SizeGuide $sizeGuide): RedirectResponse
    {
        $sizeGuide->delete();

        return to_route('admin.size-guides.index')->with('status', "“{$sizeGuide->name}” was deleted.");
    }
}
