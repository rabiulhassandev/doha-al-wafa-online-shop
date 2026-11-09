<?php

namespace App\View\Composers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Shows how many orders wait for confirmation next to "Orders" in the admin menu.
 */
class AdminNavigationComposer
{
    public function compose(View $view): void
    {
        // The menu is drawn twice per page (desktop and phone), so count once per request.
        $view->with('ordersWaiting', once(fn (): int => Gate::allows('manage-orders')
            ? Order::query()->where('status', OrderStatus::PendingConfirmation)->count()
            : 0));
    }
}
