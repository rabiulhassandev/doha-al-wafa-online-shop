<?php

namespace App\View\Composers;

use App\Enums\OrderStatus;
use App\Models\ContactMessage;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Shows how many orders wait for confirmation, and how many messages are unread, in the admin menu.
 */
class AdminNavigationComposer
{
    public function compose(View $view): void
    {
        // The menu is drawn twice per page (desktop and phone), so count once per request.
        $counts = once(fn (): array => Gate::allows('manage-orders')
            ? [
                'ordersWaiting' => Order::query()->where('status', OrderStatus::PendingConfirmation)->count(),
                'messagesUnread' => ContactMessage::query()->unread()->count(),
            ]
            : ['ordersWaiting' => 0, 'messagesUnread' => 0]);

        $view->with($counts);
    }
}
