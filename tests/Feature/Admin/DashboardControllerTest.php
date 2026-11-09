<?php

use App\Models\User;
use App\Services\Settings;

it('redirects guests to the admin sign-in page', function () {
    $this->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login');
});

it('forbids customers', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('shows the dashboard to team members', function (User $member) {
    $this->actingAs($member)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee($member->name);
})->with([
    'owner' => fn () => User::factory()->owner()->create(),
    'staff' => fn () => User::factory()->staff()->create(),
]);

it('marks setup tasks as done once their settings are saved', function () {
    app(Settings::class)->set([
        'whatsapp_number' => '+97455551234',
        'tiktok_url' => 'https://www.tiktok.com/@dohaalwafa',
    ]);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('setupTasks', [
            ['label' => 'Add the store WhatsApp number', 'done' => true],
            ['label' => 'Add a contact phone number', 'done' => false],
            ['label' => 'Add a contact email', 'done' => false],
            ['label' => 'Link at least one social media account', 'done' => true],
        ]);
});
