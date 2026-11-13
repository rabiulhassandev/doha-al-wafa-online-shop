<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Settings;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    /**
     * Show the store settings form.
     */
    public function edit(Settings $settings): View
    {
        return view('admin.settings.edit', [
            'settings' => [...$settings->all(), 'feed_token' => $settings->feedToken()],
            'contactPhone' => $settings->phone('contact_phone')?->formatted(),
            'whatsAppNumber' => $settings->phone('whatsapp_number')?->formatted(),
            'deliveryFreeOver' => Money::toInput($settings->get('delivery_free_over')),
            'secretsSaved' => [
                'meta_capi_token' => $settings->secret('meta_capi_token') !== null,
                'tiktok_events_token' => $settings->secret('tiktok_events_token') !== null,
            ],
            'feedUrls' => $this->feedUrls($settings),
        ]);
    }

    /**
     * Save the store settings.
     */
    public function update(UpdateSettingsRequest $request, Settings $settings): RedirectResponse
    {
        $settings->set($request->settingValues());

        return to_route('admin.settings.edit')->with('status', 'Settings saved.');
    }

    /**
     * Get the catalog feed addresses to paste into Meta and TikTok, per language.
     *
     * @return array<string, string>
     */
    private function feedUrls(Settings $settings): array
    {
        $token = $settings->feedToken();
        $feedUrls = [];

        foreach (['meta' => 'Meta', 'tiktok' => 'TikTok'] as $platform => $platformName) {
            foreach (config('shop.locales') as $locale => $language) {
                $feedUrls["{$platformName}, {$language['label']}"] = route('feeds.show', array_filter([
                    'platform' => $platform,
                    'locale' => $locale === config('shop.default_locale') ? null : $locale,
                    'token' => $token,
                ]));
            }
        }

        return $feedUrls;
    }
}
