<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ContactMessageRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Services\Settings;
use App\Support\QatarPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Show the store's contact details and the contact form.
     */
    public function show(): View
    {
        return view('storefront.pages.contact');
    }

    /**
     * Keep the message for the team's inbox and email it to the store.
     */
    public function store(ContactMessageRequest $request, Settings $settings): RedirectResponse
    {
        $contactMessage = ContactMessage::query()->create([
            'name' => $request->validated('name'),
            'phone' => QatarPhone::tryParse($request->validated('phone'))?->e164(),
            'email' => $request->validated('email'),
            'message' => $request->validated('message'),
            'locale' => app()->getLocale(),
            'ip_address' => $request->ip(),
        ]);

        $alertEmail = $settings->get('order_alert_email');

        if (filled($alertEmail)) {
            Mail::to($alertEmail)->locale('en')->send(new ContactMessageReceived($contactMessage));
        }

        return to_route('contact.show')->with('contact_status', __('Thank you! We received your message and will reply soon.'));
    }
}
