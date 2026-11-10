<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    /**
     * List the store pages.
     */
    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::query()->ordered()->get()]);
    }

    /**
     * Show the form for adding a page.
     */
    public function create(): View
    {
        return view('admin.pages.create', ['page' => new Page]);
    }

    /**
     * Add a page.
     */
    public function store(PageRequest $request): RedirectResponse
    {
        $page = Page::query()->create($request->validated());

        return to_route('admin.pages.index')->with('status', "“{$page->title_en}” was added.");
    }

    /**
     * Show the form for editing a page.
     */
    public function edit(Page $page): View
    {
        return view('admin.pages.edit', ['page' => $page]);
    }

    /**
     * Update a page.
     */
    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->update($request->validated());

        return to_route('admin.pages.index')->with('status', "“{$page->title_en}” was updated.");
    }

    /**
     * Delete a page.
     */
    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return to_route('admin.pages.index')->with('status', "“{$page->title_en}” was deleted.");
    }
}
