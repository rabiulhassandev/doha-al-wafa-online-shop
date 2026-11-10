<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Contracts\View\View;

/**
 * Store pages written in the admin panel, and the FAQ.
 */
class PageController extends Controller
{
    /**
     * Show a page. Hidden pages are not found.
     */
    public function show(Page $page): View
    {
        abort_unless($page->is_published, 404);

        return view('storefront.pages.show', ['page' => $page]);
    }

    /**
     * Show the frequently asked questions.
     */
    public function faq(): View
    {
        return view('storefront.pages.faq', ['faqs' => Faq::query()->published()->ordered()->get()]);
    }
}
