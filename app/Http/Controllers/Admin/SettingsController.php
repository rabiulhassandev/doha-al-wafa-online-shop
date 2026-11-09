<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Settings;
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
            'settings' => $settings->all(),
            'contactPhone' => $settings->phone('contact_phone')?->formatted(),
            'whatsAppNumber' => $settings->phone('whatsapp_number')?->formatted(),
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
}
