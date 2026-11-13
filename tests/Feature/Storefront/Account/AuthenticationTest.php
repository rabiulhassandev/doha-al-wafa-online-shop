<?php

use App\Actions\ChangePassword;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Build a complete sign-up form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return [
        'name' => 'Fatima Al-Kuwari',
        'email' => 'Fatima@Example.com',
        'phone' => '5555 1234',
        'password' => 'secret-pass-1',
        'password_confirmation' => 'secret-pass-1',
        ...$overrides,
    ];
}

describe('signing up', function () {
    it('creates a customer account and signs it in', function () {
        $this->post(route('account.register.store', ['locale' => 'ar']), registrationPayload())
            ->assertRedirect(route('account.orders', ['locale' => 'ar']));

        $user = User::query()->sole();
        expect([$user->role, $user->email, $user->phone, $user->locale])->toBe([UserRole::Customer, 'fatima@example.com', '+97455551234', 'ar']);
        $this->assertAuthenticatedAs($user);
    });

    it('goes back to checkout when asked', function () {
        $this->post(route('account.register.store', ['locale' => 'en']), registrationPayload(['next' => 'checkout']))
            ->assertRedirect(route('checkout.show', ['locale' => 'en']));
    });

    it('explains mistakes in the shopper’s language', function () {
        User::factory()->create(['email' => 'fatima@example.com']);

        $this->post(route('account.register.store', ['locale' => 'ar']), registrationPayload(['password_confirmation' => 'other-pass-1', 'phone' => '4444 1234']))
            ->assertSessionHasErrors([
                'email' => 'هناك حساب بهذا البريد الإلكتروني بالفعل. سجّل الدخول بدلاً من ذلك.',
                'password' => 'كلمتا المرور غير متطابقتين.',
                'phone' => 'أدخل رقم جوال قطري من 8 أرقام.',
            ]);

        $this->assertGuest();
    });
});

describe('signing in', function () {
    it('signs in with email and password and remembers the language for emails', function () {
        $user = User::factory()->create(['email' => 'fatima@example.com', 'password' => 'secret-pass-1', 'locale' => 'en']);

        $this->post(route('account.login.store', ['locale' => 'ar']), ['email' => 'FATIMA@example.com', 'password' => 'secret-pass-1'])
            ->assertRedirect(route('account.orders', ['locale' => 'ar']));

        $this->assertAuthenticatedAs($user);
        expect($user->refresh()->locale)->toBe('ar');
    });

    it('refuses a wrong password and slows down guessing', function () {
        User::factory()->create(['email' => 'fatima@example.com', 'password' => 'secret-pass-1']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('account.login.store', ['locale' => 'en']), ['email' => 'fatima@example.com', 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => 'The email or password is not right.']);
        }

        $this->post(route('account.login.store', ['locale' => 'en']), ['email' => 'fatima@example.com', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('signs out', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('account.logout', ['locale' => 'en']))
            ->assertRedirect(route('home', ['locale' => 'en']));

        $this->assertGuest();
    });

    it('sends guests to the sign-in page in their language', function () {
        $this->get(route('account.orders', ['locale' => 'ar']))
            ->assertRedirect(route('account.login', ['locale' => 'ar']));
    });

    it('sends signed-in shoppers from the sign-in page to their orders', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('account.login', ['locale' => 'en']))
            ->assertRedirect(route('account.orders', ['locale' => 'en']));
    });

    it('keeps the admin panel’s own sign-in page and keeps customers out of it', function () {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    });

    it('requires staff to use the admin sign-in page', function () {
        $user = User::factory()->staff()->create(['password' => 'staff-password-1']);
        $details = ['email' => $user->email, 'password' => 'staff-password-1'];

        $this->post(route('account.login.store', ['locale' => 'en']), $details)->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('admin.login.store'), $details)->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    });
});

describe('forgotten password', function () {
    it('emails a link to the store’s own page in the account’s language', function () {
        Notification::fake();
        config(['app.url' => 'https://shop.example.test']);
        $user = User::factory()->create(['email' => 'fatima@example.com', 'locale' => 'ar']);

        $this->post(route('account.password.email', ['locale' => 'en']), ['email' => 'fatima@example.com'])
            ->assertSessionHas('account_status', 'If an account uses this email, we sent it a link to choose a new password.');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification, array $channels, User $notifiable, string $locale): bool {
            App::setLocale($locale);
            $html = (string) $notification->toMail($notifiable)->render();

            return $locale === 'ar'
                && str_contains($html, 'https://shop.example.test/ar/account/reset-password/'.$notification->token)
                && str_contains($html, 'dir="rtl"')
                && str_contains($html, 'اختر كلمة مرور جديدة');
        });
    });

    it('gives the same answer for an unknown email', function () {
        Notification::fake();

        $this->post(route('account.password.email', ['locale' => 'en']), ['email' => 'nobody@example.com'])
            ->assertSessionHas('account_status', 'If an account uses this email, we sent it a link to choose a new password.');

        Notification::assertNothingSent();
    });

    it('sets a new password with a valid link', function () {
        $user = User::factory()->create(['email' => 'fatima@example.com']);
        $token = Password::createToken($user);

        $this->post(route('account.password.store', ['locale' => 'en']), [
            'token' => $token,
            'email' => 'fatima@example.com',
            'password' => 'new-secret-2',
            'password_confirmation' => 'new-secret-2',
        ])->assertRedirect(route('account.login', ['locale' => 'en']));

        $this->post(route('account.login.store', ['locale' => 'en']), ['email' => 'fatima@example.com', 'password' => 'new-secret-2']);
        $this->assertAuthenticatedAs($user);
    });

    it('refuses an old or altered link', function () {
        User::factory()->create(['email' => 'fatima@example.com']);

        $this->post(route('account.password.store', ['locale' => 'en']), [
            'token' => 'not-a-real-token',
            'email' => 'fatima@example.com',
            'password' => 'new-secret-2',
            'password_confirmation' => 'new-secret-2',
        ])->assertSessionHasErrors(['email' => 'This link is no longer valid. Ask for a new one below.']);
    });

    it('applies the staff password policy during recovery', function () {
        $user = User::factory()->staff()->create();
        $token = Password::createToken($user);
        $reset = route('account.password.store', ['locale' => 'en']);
        $details = ['token' => $token, 'email' => $user->email];

        $this->post($reset, $details + ['password' => 'short123', 'password_confirmation' => 'short123'])
            ->assertSessionHasErrors('password');

        $this->post($reset, $details + ['password' => 'new-secret-2', 'password_confirmation' => 'new-secret-2'])
            ->assertRedirect(route('admin.login'));

        expect(Hash::check('new-secret-2', $user->fresh()->password))->toBeTrue();
    });

    it('ends an earlier authenticated session after the password changes', function () {
        $user = User::factory()->create(['password' => 'old-password-1', 'remember_token' => 'previous-token']);
        $this->post(route('account.login.store', ['locale' => 'en']), ['email' => $user->email, 'password' => 'old-password-1'])
            ->assertSessionHas('password_hash_web');
        $oldFingerprint = session('password_hash_web');

        app(ChangePassword::class)->handle($user, 'new-password-2');

        expect($user->remember_token)->not->toBe('previous-token');
        $this->actingAs($user)->withSession(['password_hash_web' => $oldFingerprint])
            ->get(route('account.orders', ['locale' => 'en']))
            ->assertRedirect(route('account.login', ['locale' => 'en']));
        $this->assertGuest();
    });
});
