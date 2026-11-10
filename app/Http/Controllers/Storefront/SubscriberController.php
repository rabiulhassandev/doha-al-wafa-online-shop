<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Support\QatarPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * "Join our community": a WhatsApp number or an email to receive news and offers.
 */
class SubscriberController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('subscribe', [
            'contact' => ['required', 'string', 'max:190'],
            'website' => ['prohibited'],
        ], [
            'contact.required' => __('Enter your WhatsApp number or email.'),
            'website' => __('Something went wrong. Please refresh the page and try again.'),
        ]);

        $contact = trim($validated['contact']);
        $attributes = $this->contactAttributes($contact)
            ?? throw ValidationException::withMessages(['contact' => __('Enter a Qatar mobile number or a valid email.')])->errorBag('subscribe');

        Subscriber::query()->firstOrCreate($attributes, [
            'locale' => app()->getLocale(),
            'source' => 'footer',
            'ip_address' => $request->ip(),
        ]);

        return back()
            ->withFragment('newsletter')
            ->with('subscribe_status', __('Thank you for joining! You will hear about new arrivals and offers first.'));
    }

    /**
     * Read a typed WhatsApp number or email.
     *
     * @return array{phone: string}|array{email: string}|null
     */
    private function contactAttributes(string $contact): ?array
    {
        if (str_contains($contact, '@')) {
            $email = Str::lower($contact);

            return Validator::make(['email' => $email], ['email' => ['email', 'max:190']])->passes() ? ['email' => $email] : null;
        }

        $phone = QatarPhone::tryParse($contact);

        return $phone !== null && $phone->isMobile() ? ['phone' => $phone->e164()] : null;
    }
}
