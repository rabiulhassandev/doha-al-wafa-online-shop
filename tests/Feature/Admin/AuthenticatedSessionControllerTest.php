<?php

use App\Models\User;

it('renders the sign-in form', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('Sign in');
});

it('signs in a team member', function () {
    $staff = User::factory()->staff()->create();

    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])
        ->assertRedirectToRoute('admin.dashboard');

    $this->assertAuthenticatedAs($staff);
});

it('rejects a wrong password', function () {
    $owner = User::factory()->owner()->create();

    $this->post(route('admin.login.store'), ['email' => $owner->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

    $this->assertGuest();
});

it('rejects customers even with the right password', function () {
    $customer = User::factory()->create();

    $this->post(route('admin.login.store'), ['email' => $customer->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

    $this->assertGuest();
});

it('locks the account out after five failed attempts', function () {
    $this->freezeTime();
    $owner = User::factory()->owner()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.login.store'), ['email' => $owner->email, 'password' => 'wrong-password']);
    }

    $this->post(route('admin.login.store'), ['email' => $owner->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Too many login attempts. Please try again in 60 seconds.']);

    $this->assertGuest();
});

it('requires the email and password', function () {
    $this->post(route('admin.login.store'))
        ->assertSessionHasErrors([
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ]);
});

it('signs the team member out', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->post(route('admin.logout'))
        ->assertRedirectToRoute('admin.login');

    $this->assertGuest();
});
