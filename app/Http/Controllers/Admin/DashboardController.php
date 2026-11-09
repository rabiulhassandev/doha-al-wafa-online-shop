<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    private const SOCIAL_SETTINGS = ['instagram_url', 'tiktok_url', 'facebook_url', 'snapchat_url'];

    /**
     * Show the admin dashboard with the store setup checklist.
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

        return view('admin.dashboard', ['setupTasks' => $setupTasks]);
    }
}
