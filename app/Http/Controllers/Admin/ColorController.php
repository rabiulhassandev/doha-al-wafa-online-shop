<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImagePreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ColorRequest;
use App\Models\Color;
use App\Services\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ColorController extends Controller
{
    /**
     * List the color palette.
     */
    public function index(): View
    {
        return view('admin.colors.index', [
            'colors' => Color::query()->ordered()->paginate(100),
        ]);
    }

    /**
     * Show the form for adding a color.
     */
    public function create(): View
    {
        return view('admin.colors.create', ['color' => new Color(['hex' => '#1e1a17'])]);
    }

    /**
     * Add a color to the palette.
     */
    public function store(ColorRequest $request, ImageStore $images): RedirectResponse
    {
        $color = new Color($request->colorAttributes());
        $color->swatch_path = $images->resolve(null, $request->file('swatch'), false, ImagePreset::ColorSwatch);
        $color->save();

        return to_route('admin.colors.index')->with('status', "“{$color->name_en}” was added to the palette.");
    }

    /**
     * Show the form for editing a color.
     */
    public function edit(Color $color): View
    {
        return view('admin.colors.edit', ['color' => $color]);
    }

    /**
     * Update a color.
     */
    public function update(ColorRequest $request, Color $color, ImageStore $images): RedirectResponse
    {
        $color->fill($request->colorAttributes());
        $color->swatch_path = $images->resolve(
            $color->swatch_path,
            $request->file('swatch'),
            $request->boolean('remove_swatch'),
            ImagePreset::ColorSwatch,
        );
        $color->save();

        return to_route('admin.colors.index')->with('status', "“{$color->name_en}” was updated.");
    }

    /**
     * Remove a color from the palette.
     */
    public function destroy(Color $color): RedirectResponse
    {
        $color->delete();

        return to_route('admin.colors.index')->with('status', "“{$color->name_en}” was deleted.");
    }
}
