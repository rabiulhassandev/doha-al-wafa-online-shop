<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShortLinkRequest;
use App\Models\Coupon;
use App\Models\ShortLink;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShortLinkController extends Controller
{
    /**
     * List the short links with their visits, the orders they brought and those orders' value.
     */
    public function index(): View
    {
        $shortLinks = ShortLink::query()
            ->withCount(['orders' => fn (Builder $query) => $query->whereNot('status', OrderStatus::Cancelled)])
            ->withSum(['orders as sales' => fn (Builder $query) => $query->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])], 'grand_total')
            ->latest()
            ->orderByDesc('id')
            ->get();

        return view('admin.short-links.index', ['shortLinks' => $shortLinks]);
    }

    /**
     * Show the form for making a short link, filled in when opened from a product.
     */
    public function create(Request $request): View
    {
        $shortLink = new ShortLink([
            'label' => $request->string('label')->limit(100, '')->toString(),
            'destination' => $request->string('destination')->limit(500, '')->toString(),
            'utm_medium' => 'social',
        ]);

        return view('admin.short-links.create', ['shortLink' => $shortLink, 'couponOptions' => $this->couponOptions()]);
    }

    /**
     * Make a short link.
     */
    public function store(ShortLinkRequest $request): RedirectResponse
    {
        $shortLink = ShortLink::query()->create($request->shortLinkAttributes());

        return to_route('admin.short-links.index')->with('status', "The link {$shortLink->shortUrl()} is ready to share.");
    }

    /**
     * Show the form for editing a short link.
     */
    public function edit(ShortLink $shortLink): View
    {
        return view('admin.short-links.edit', ['shortLink' => $shortLink, 'couponOptions' => $this->couponOptions()]);
    }

    /**
     * Update a short link.
     */
    public function update(ShortLinkRequest $request, ShortLink $shortLink): RedirectResponse
    {
        $shortLink->update($request->shortLinkAttributes($shortLink));

        return to_route('admin.short-links.index')->with('status', "The link “{$shortLink->label}” was updated.");
    }

    /**
     * Delete a short link that brought no orders. Others stay for the order history; turn them off instead.
     */
    public function destroy(ShortLink $shortLink): RedirectResponse
    {
        if ($shortLink->orders()->exists()) {
            return to_route('admin.short-links.index')->with('error', "The link “{$shortLink->label}” brought orders, so it stays for their history. Turn it off instead.");
        }

        $shortLink->delete();

        return to_route('admin.short-links.index')->with('status', "The link “{$shortLink->label}” was deleted.");
    }

    /**
     * Get the discount codes a link can apply.
     *
     * @return array<string, string>
     */
    private function couponOptions(): array
    {
        return ['' => 'None', ...Coupon::query()->orderBy('code')->pluck('code', 'code')->all()];
    }
}
