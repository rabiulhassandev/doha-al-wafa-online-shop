<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Gives the page the wishlist saved on the signed-in shopper's account, so the browser's
 * wishlist can merge with it and keep it up to date.
 */
class AccountWishlistComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        $view->with('accountWishlist', $user === null ? null : [
            'url' => route('account.wishlist.sync'),
            'ids' => $user->wishlistProducts()->orderByPivot('created_at', 'desc')->pluck('products.id')->all(),
        ]);
    }
}
