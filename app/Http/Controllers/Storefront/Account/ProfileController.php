<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\ProfileRequest;
use App\Support\CanonicalUrl;
use App\Support\QatarPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * The shopper's name, email and mobile number, and their password.
 */
class ProfileController extends Controller
{
    private const PENDING_EMAIL = 'account.pending_email';

    public function edit(Request $request): View
    {
        return view('storefront.account.profile', [
            'user' => $request->user(),
            'phone' => QatarPhone::tryParse($request->user()->phone)?->formatted(),
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $email = $request->validated('email');
        $user->update([
            'name' => $request->validated('name'),
            'phone' => QatarPhone::tryParse($request->validated('phone'))?->e164(),
        ]);

        if ($email !== $user->email) {
            $token = Str::random(48);
            $path = URL::temporarySignedRoute('account.profile.email.show', now()->addMinutes(30), [
                'locale' => app()->getLocale(),
                'token' => $token,
            ], absolute: false);
            $url = CanonicalUrl::forPath($path);

            Mail::raw(__('Confirm your new email address using this link: :url', ['url' => $url]),
                fn ($message) => $message->to($email)->subject(__('Confirm your new email address')));

            $request->session()->put(self::PENDING_EMAIL, [
                'token' => $token,
                'user_id' => $user->getKey(),
                'from' => $user->email,
                'to' => $email,
                'password_fingerprint' => hash('sha256', $user->getAuthPassword()),
            ]);

            return to_route('account.profile.edit')->with('account_status', __('Open the confirmation link in this browser to finish changing your email.'));
        }

        return to_route('account.profile.edit')->with('account_status', __('Your details were saved.'));
    }

    public function showEmailConfirmation(Request $request, string $token): Response
    {
        $this->pendingEmail($request, $token);

        return response()->view('storefront.account.confirm-email')
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function confirmEmail(Request $request, string $token): RedirectResponse
    {
        $pending = $this->pendingEmail($request, $token);
        $user = $request->user();

        try {
            $changed = $user->newQuery()->whereKey($user->getKey())
                ->where('email', $pending['from'])
                ->where('password', $user->getAuthPassword())
                ->update(['email' => $pending['to'], 'email_verified_at' => now()]);
        } catch (UniqueConstraintViolationException) {
            return to_route('account.profile.edit')->withErrors(['email' => __('Another account already uses this email.')]);
        }

        abort_unless($changed === 1, 403);
        $request->session()->forget(self::PENDING_EMAIL);
        Password::deleteToken($user);
        $user->refresh();
        Password::deleteToken($user);

        rescue(fn () => Mail::raw(__('The email address on your account was changed. If you did not make this change, contact us.'),
            fn ($message) => $message->to($pending['from'])->subject(__('Your account email was changed'))));

        return to_route('account.profile.edit')->with('account_status', __('Your email was changed.'));
    }

    /**
     * @return array{token: string, user_id: int, from: string, to: string, password_fingerprint: string}
     */
    private function pendingEmail(Request $request, string $token): array
    {
        $pending = $request->session()->get(self::PENDING_EMAIL);
        $user = $request->user();

        abort_unless(is_array($pending)
            && hash_equals($pending['token'], $token)
            && $pending['user_id'] === $user->getKey()
            && $pending['from'] === $user->email
            && hash_equals($pending['password_fingerprint'], hash('sha256', $user->getAuthPassword())), 403);

        return $pending;
    }
}
