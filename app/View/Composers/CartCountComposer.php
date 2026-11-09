<?php

namespace App\View\Composers;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shares how many pieces are in the shopper's bag with the header and the slide-in bag.
 */
class CartCountComposer
{
    public function __construct(
        private readonly Request $request,
        private readonly CartService $cartService,
    ) {}

    public function compose(View $view): void
    {
        $view->with('cartCount', once(fn (): int => $this->cartService->count($this->request)));
    }
}
