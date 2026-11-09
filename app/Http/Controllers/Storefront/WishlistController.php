<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class WishlistController extends Controller
{
    /**
     * Show the wishlist. Saved items live in the shopper's browser and load on the page.
     */
    public function __invoke(): View
    {
        return view('storefront.wishlist');
    }
}
