<?php

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

describe('contact form', function () {
    it('keeps the message for the team and emails the store', function () {
        app(Settings::class)->set(['order_alert_email' => 'orders@example.com']);

        $this->post(route('contact.store', ['locale' => 'ar']), ['name' => 'فاطمة', 'phone' => '٥٥٥٥ ١٢٣٤', 'email' => '', 'message' => 'هل يتوفر المقاس 58؟', 'website' => ''])
            ->assertRedirectToRoute('contact.show', ['locale' => 'ar'])
            ->assertSessionHas('contact_status', 'شكراً لك! استلمنا رسالتك وسنرد عليك قريباً.');

        $message = ContactMessage::query()->sole();
        expect([$message->name, $message->phone, $message->locale, $message->read_at])->toBe(['فاطمة', '+97455551234', 'ar', null]);
        Mail::assertQueued(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasTo('orders@example.com'));
    });

    it('needs a way to reply', function () {
        $this->post(route('contact.store', ['locale' => 'en']), ['name' => 'Fatima', 'phone' => '', 'email' => '', 'message' => 'Hello'])
            ->assertSessionHasErrors(['phone' => 'Enter your phone number or your email so we can reply.']);

        $this->assertDatabaseCount('contact_messages', 0);
    });

    it('accepts an email instead of a phone number', function () {
        $this->post(route('contact.store', ['locale' => 'en']), ['name' => 'Fatima', 'phone' => '', 'email' => 'fatima@example.com', 'message' => 'Hello'])
            ->assertSessionHasNoErrors();

        expect(ContactMessage::query()->sole()->email)->toBe('fatima@example.com');
    });

    it('turns away bots that fill the hidden field', function () {
        $this->post(route('contact.store', ['locale' => 'en']), ['name' => 'Bot', 'phone' => '5555 1234', 'message' => 'Spam', 'website' => 'https://spam.example'])
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('contact_messages', 0);
    });
});

describe('inbox', function () {
    it('shows unread messages first and counts them in the menu', function () {
        ContactMessage::factory()->read()->create(['name' => 'Already read', 'created_at' => now()]);
        ContactMessage::factory()->create(['name' => 'Not read yet', 'created_at' => now()->subDay()]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSeeInOrder(['Not read yet', 'Already read'])
            ->assertSee('(1 unread)');
    });

    it('marks a message read when opened', function () {
        $message = ContactMessage::factory()->create(['message' => 'Is size 58 back?']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.messages.show', $message))
            ->assertOk()
            ->assertSee('Is size 58 back?')
            ->assertSee('Reply on WhatsApp');

        expect($message->refresh()->read_at)->not->toBeNull();
    });

    it('deletes a message', function () {
        $message = ContactMessage::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('admin.messages.destroy', $message))
            ->assertRedirectToRoute('admin.messages.index');

        $this->assertModelMissing($message);
    });
});

describe('joining the community', function () {
    it('signs up a WhatsApp number or an email once', function (string $typed, string $column, string $stored) {
        $this->from(route('home', ['locale' => 'en']))
            ->post(route('subscribers.store', ['locale' => 'en']), ['contact' => $typed, 'website' => ''])
            ->assertRedirect(route('home', ['locale' => 'en']).'#newsletter')
            ->assertSessionHas('subscribe_status');

        $this->post(route('subscribers.store', ['locale' => 'en']), ['contact' => $typed]);

        expect(Subscriber::query()->sole()->{$column})->toBe($stored);
    })->with([
        'WhatsApp number' => ['+974 5555 1234', 'phone', '+97455551234'],
        'email' => ['Fatima@Example.com', 'email', 'fatima@example.com'],
    ]);

    it('explains what it needs, in the shopper’s language', function () {
        $this->post(route('subscribers.store', ['locale' => 'ar']), ['contact' => '4444 1234'])
            ->assertSessionHasErrors(['contact' => 'أدخل رقم جوال قطري أو بريداً إلكترونياً صحيحاً.'], errorBag: 'subscribe');

        $this->assertDatabaseCount('subscribers', 0);
    });

    it('lists, downloads and removes subscribers', function () {
        $subscriber = Subscriber::factory()->create(['phone' => '+97455551234']);
        Subscriber::factory()->create(['phone' => null, 'email' => 'noora@example.com']);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('admin.subscribers.index'))->assertOk()->assertSee('+974 5555 1234')->assertSee('noora@example.com');

        $csv = $this->get(route('admin.subscribers.export'))->assertOk()->streamedContent();
        expect($csv)->toContain('+97455551234')->toContain('noora@example.com');

        $this->delete(route('admin.subscribers.destroy', $subscriber))->assertRedirectToRoute('admin.subscribers.index');
        $this->assertModelMissing($subscriber);
    });
});
