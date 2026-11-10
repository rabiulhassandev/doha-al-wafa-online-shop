<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\Settings;

/**
 * Build a complete settings form submission.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function settingsPayload(array $overrides = []): array
{
    return [
        'store_name' => 'Doha AL WAFA',
        'contact_phone' => '',
        'whatsapp_number' => '',
        'contact_email' => '',
        'instagram_url' => '',
        'tiktok_url' => '',
        'facebook_url' => '',
        'snapchat_url' => '',
        'announcement_en' => '',
        'announcement_ar' => '',
        'low_stock_threshold' => '3',
        'delivery_free_over' => '',
        'delivery_days_off' => ['5'],
        'preferred_date_enabled' => '1',
        'max_quantity_per_item' => '10',
        'order_alert_email' => '',
        'consent_bar_enabled' => '0',
        'whatsapp_button_enabled' => '1',
        ...$overrides,
    ];
}

describe('edit', function () {
    it('shows saved phone numbers in a readable format', function () {
        app(Settings::class)->set(['whatsapp_number' => '+97455551234']);

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('value="+974 5555 1234"', false);
    });

    it('forbids staff', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    });
});

describe('update', function () {
    it('saves phone numbers in international format', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.settings.update'), settingsPayload([
                'whatsapp_number' => '5555 1234',
                'contact_phone' => '00974 4444 1234',
                'instagram_url' => 'https://www.instagram.com/dohaalwafa',
            ]))
            ->assertRedirectToRoute('admin.settings.edit')
            ->assertSessionHas('status', 'Settings saved.');

        expect(Setting::query()->where('key', 'whatsapp_number')->value('value'))->toBe('+97455551234');
        expect(Setting::query()->where('key', 'contact_phone')->value('value'))->toBe('+97444441234');
        expect(Setting::query()->where('key', 'instagram_url')->value('value'))->toBe('https://www.instagram.com/dohaalwafa');
    });

    it('clears a setting when its field is emptied', function () {
        app(Settings::class)->set(['contact_email' => 'hello@example.com']);

        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.settings.update'), settingsPayload(['contact_email' => '']))
            ->assertRedirectToRoute('admin.settings.edit');

        expect(Setting::query()->where('key', 'contact_email')->value('value'))->toBeNull();
    });

    it('rejects a landline as the WhatsApp number', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.settings.update'), settingsPayload(['whatsapp_number' => '4444 1234']))
            ->assertSessionHasErrors(['whatsapp_number' => 'The WhatsApp number must be a Qatar mobile number.']);

        $this->assertDatabaseCount('settings', 0);
    });

    it('rejects a phone number from outside Qatar', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.settings.update'), settingsPayload(['contact_phone' => '+971 50 123 4567']))
            ->assertSessionHasErrors(['contact_phone' => 'The contact phone must be a valid Qatar phone number.']);
    });

    it('rejects social links that do not use https', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.settings.update'), settingsPayload(['instagram_url' => 'http://instagram.com/dohaalwafa']))
            ->assertSessionHasErrors(['instagram_url' => 'The Instagram link field must be a valid URL.']);
    });

    it('requires a store name', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.settings.update'), settingsPayload(['store_name' => '']))
            ->assertSessionHasErrors(['store_name' => 'The store name field is required.']);
    });

    it('forbids staff', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->put(route('admin.settings.update'), settingsPayload(['store_name' => 'Changed']))
            ->assertForbidden();

        $this->assertDatabaseCount('settings', 0);
    });
});

it('saves the delivery settings', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->put(route('admin.settings.update'), settingsPayload([
            'delivery_free_over' => '300',
            'delivery_days_off' => ['5', '6'],
            'max_quantity_per_item' => '5',
        ]))
        ->assertRedirectToRoute('admin.settings.edit');

    expect(Setting::query()->where('key', 'delivery_free_over')->value('value'))->toBe(30000);
    expect(Setting::query()->where('key', 'delivery_days_off')->value('value'))->toBe([5, 6]);
    expect(Setting::query()->where('key', 'max_quantity_per_item')->value('value'))->toBe(5);
});

it('saves no days off when every day is unticked', function () {
    $payload = settingsPayload();
    unset($payload['delivery_days_off']);

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('admin.settings.update'), $payload)
        ->assertRedirectToRoute('admin.settings.edit');

    expect(Setting::query()->where('key', 'delivery_days_off')->value('value'))->toBe([]);
});

it('saves where new orders are emailed and whether shoppers pick a delivery date', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->put(route('admin.settings.update'), settingsPayload([
            'order_alert_email' => 'orders@example.com',
            'preferred_date_enabled' => '0',
        ]))
        ->assertRedirectToRoute('admin.settings.edit');

    expect(Setting::query()->where('key', 'order_alert_email')->value('value'))->toBe('orders@example.com');
    expect(Setting::query()->where('key', 'preferred_date_enabled')->value('value'))->toBeFalse();
});

it('rejects an invalid new order email', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->put(route('admin.settings.update'), settingsPayload(['order_alert_email' => 'not an email']))
        ->assertSessionHasErrors('order_alert_email');
});

it('saves the trust badge, cookie and WhatsApp settings', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->put(route('admin.settings.update'), settingsPayload([
            'exchange_promise_en' => 'Exchange within 7 days',
            'consent_bar_enabled' => '1',
            'whatsapp_message_on_the_way_ar' => 'طلبك :number في الطريق.',
        ]))
        ->assertRedirectToRoute('admin.settings.edit');

    $settings = app(Settings::class);
    expect($settings->get('exchange_promise_en'))->toBe('Exchange within 7 days');
    expect($settings->get('consent_bar_enabled'))->toBeTrue();
    expect($settings->get('whatsapp_message_on_the_way_ar'))->toBe('طلبك :number في الطريق.');
});

it('shows the built-in WhatsApp wording as a guide', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Hello :name, this is :store.')
        ->assertSee('مرحباً :name، معك :store.');
});

describe('ads and analytics', function () {
    it('saves the pixel IDs and keeps access tokens encrypted', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.settings.update'), settingsPayload([
                'meta_pixel_id' => '1234567890123456',
                'meta_capi_token' => 'EAAB-meta-token',
                'tiktok_pixel_id' => 'C1A2B3C4D5E6F7G8H9I0',
                'tiktok_events_token' => 'tiktok-token',
                'snap_pixel_id' => '1a2b3c4d-1a2b-1a2b-1a2b-1a2b3c4d5e6f',
                'ga4_measurement_id' => 'G-AB12CD34EF',
                'whatsapp_button_enabled' => '0',
            ]))
            ->assertRedirectToRoute('admin.settings.edit')
            ->assertSessionHasNoErrors();

        $settings = app(Settings::class);
        expect([$settings->get('meta_pixel_id'), $settings->get('tiktok_pixel_id'), $settings->get('snap_pixel_id'), $settings->get('ga4_measurement_id')])
            ->toBe(['1234567890123456', 'C1A2B3C4D5E6F7G8H9I0', '1a2b3c4d-1a2b-1a2b-1a2b-1a2b3c4d5e6f', 'G-AB12CD34EF']);
        expect($settings->secret('meta_capi_token'))->toBe('EAAB-meta-token');
        expect($settings->secret('tiktok_events_token'))->toBe('tiktok-token');
        expect(Setting::query()->where('key', 'meta_capi_token')->value('value'))->not->toContain('EAAB-meta-token');
        expect($settings->get('whatsapp_button_enabled'))->toBeFalse();
    });

    it('keeps a saved token when its field is left empty, and removes it on request', function () {
        app(Settings::class)->set([
            'meta_capi_token' => Settings::encryptSecret('meta-token'),
            'tiktok_events_token' => Settings::encryptSecret('tiktok-token'),
        ]);

        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.settings.update'), settingsPayload([
                'meta_capi_token' => '',
                'tiktok_events_token' => '',
                'tiktok_events_token_clear' => '1',
            ]))
            ->assertSessionHasNoErrors();

        $settings = app(Settings::class);
        expect($settings->secret('meta_capi_token'))->toBe('meta-token');
        expect($settings->secret('tiktok_events_token'))->toBeNull();
        expect(Setting::query()->whereIn('key', ['meta_capi_token_clear', 'tiktok_events_token_clear'])->exists())->toBeFalse();
    });

    it('never shows a saved token', function () {
        app(Settings::class)->set(['meta_capi_token' => Settings::encryptSecret('EAAB-meta-token')]);

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('A token is saved. Leave this empty to keep it.')
            ->assertDontSee('EAAB-meta-token');
    });

    it('checks the IDs', function (string $field, string $value) {
        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.settings.update'), settingsPayload([$field => $value]))
            ->assertSessionHasErrors($field);
    })->with([
        'Meta pixel with letters' => ['meta_pixel_id', 'abc123'],
        'TikTok pixel with spaces' => ['tiktok_pixel_id', 'C1A2 B3C4'],
        'Snap pixel not in its format' => ['snap_pixel_id', '12345'],
        'GA4 without G-' => ['ga4_measurement_id', 'UA-12345-1'],
        'feed password too short' => ['feed_token', 'abc'],
    ]);

    it('shows the feed addresses with the feed password', function () {
        app(Settings::class)->set(['feed_token' => 'secret-feed-123']);

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee(url('feeds/meta.csv').'?token=secret-feed-123')
            ->assertSee(e(url('feeds/tiktok.csv').'?locale=ar&token=secret-feed-123'), false);
    });
});
