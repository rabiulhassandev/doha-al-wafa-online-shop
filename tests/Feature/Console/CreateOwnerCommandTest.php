<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an owner account', function () {
    $this->artisan('shop:create-owner', ['email' => 'Owner@Example.com', '--name' => 'Hessa'])
        ->expectsQuestion('Password', 'secret12345')
        ->assertSuccessful();

    $owner = User::query()->where('email', 'owner@example.com')->sole();
    expect($owner->name)->toBe('Hessa');
    expect($owner->role)->toBe(UserRole::Owner);
    expect(Hash::check('secret12345', $owner->password))->toBeTrue();
});

it('refuses an email that already has an account', function () {
    User::factory()->create(['email' => 'owner@example.com']);

    $this->artisan('shop:create-owner', ['email' => 'owner@example.com', '--name' => 'Hessa'])
        ->expectsOutputToContain('The email has already been taken.')
        ->assertFailed();

    $this->assertDatabaseCount('users', 1);
});
