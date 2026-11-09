<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImagePreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Services\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BannerController extends Controller
{
    /**
     * List the home page banners.
     */
    public function index(): View
    {
        return view('admin.banners.index', ['banners' => Banner::query()->ordered()->get()]);
    }

    /**
     * Show the form for adding a banner.
     */
    public function create(): View
    {
        return view('admin.banners.create', ['banner' => new Banner]);
    }

    /**
     * Add a banner.
     */
    public function store(BannerRequest $request, ImageStore $images): RedirectResponse
    {
        $banner = new Banner($request->bannerAttributes());
        $banner->image_path = $images->store($request->file('image'), ImagePreset::BannerDesktop);
        $banner->mobile_image_path = $images->resolve(null, $request->file('mobile_image'), false, ImagePreset::BannerMobile);
        $banner->save();

        return to_route('admin.banners.index')->with('status', 'The banner was added.');
    }

    /**
     * Show the form for editing a banner.
     */
    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', ['banner' => $banner]);
    }

    /**
     * Update a banner.
     */
    public function update(BannerRequest $request, Banner $banner, ImageStore $images): RedirectResponse
    {
        $banner->fill($request->bannerAttributes());
        $banner->image_path = $images->resolve($banner->image_path, $request->file('image'), false, ImagePreset::BannerDesktop);
        $banner->mobile_image_path = $images->resolve(
            $banner->mobile_image_path,
            $request->file('mobile_image'),
            $request->boolean('remove_mobile_image'),
            ImagePreset::BannerMobile,
        );
        $banner->save();

        return to_route('admin.banners.index')->with('status', 'The banner was updated.');
    }

    /**
     * Delete a banner and its images.
     */
    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return to_route('admin.banners.index')->with('status', 'The banner was deleted.');
    }
}
