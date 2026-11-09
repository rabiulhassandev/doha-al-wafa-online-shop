<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Settings;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    private const SOCIAL_SETTINGS = ['instagram_url', 'tiktok_url', 'facebook_url', 'snapchat_url'];

    /**
     * Show the admin dashboard: today's orders and the store setup checklist.
     */
    public function __invoke(Settings $settings): View
    {
        $setupTasks = [
            ['label' => 'Add the store WhatsApp number', 'done' => filled($settings->get('whatsapp_number'))],
            ['label' => 'Add a contact phone number', 'done' => filled($settings->get('contact_phone'))],
            ['label' => 'Add a contact email', 'done' => filled($settings->get('contact_email'))],
            [
                'label' => 'Link at least one social media account',
                'done' => collect(self::SOCIAL_SETTINGS)->contains(fn (string $key): bool => filled($settings->get($key))),
            ],
        ];

        $todaysOrders = Order::query()->where('placed_at', '>=', today())->whereNot('status', OrderStatus::Cancelled);

        return view('admin.dashboard', [
            'setupTasks' => $setupTasks,
            'orderStats' => [
                'waiting' => Order::query()->where('status', OrderStatus::PendingConfirmation)->count(),
                'todayCount' => (clone $todaysOrders)->count(),
                'todayTotal' => (int) (clone $todaysOrders)->sum('grand_total'),
                'outForDelivery' => Order::query()->where('status', OrderStatus::OutForDelivery)->count(),
            ],
        ]);
    }
}
