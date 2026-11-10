<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FaqController extends Controller
{
    /**
     * List the frequently asked questions.
     */
    public function index(): View
    {
        return view('admin.faqs.index', ['faqs' => Faq::query()->ordered()->get()]);
    }

    /**
     * Show the form for adding a question.
     */
    public function create(): View
    {
        return view('admin.faqs.create', ['faq' => new Faq(['sort_order' => ((int) Faq::query()->max('sort_order')) + 10])]);
    }

    /**
     * Add a question.
     */
    public function store(FaqRequest $request): RedirectResponse
    {
        Faq::query()->create($request->validated());

        return to_route('admin.faqs.index')->with('status', 'The question was added.');
    }

    /**
     * Show the form for editing a question.
     */
    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', ['faq' => $faq]);
    }

    /**
     * Update a question.
     */
    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return to_route('admin.faqs.index')->with('status', 'The question was updated.');
    }

    /**
     * Delete a question.
     */
    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return to_route('admin.faqs.index')->with('status', 'The question was deleted.');
    }
}
