<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('only team members may access the admin panel', function (UserRole $role, bool $allowed) {
    $user = User::factory()->make(['role' => $role]);

    expect(Gate::forUser($user)->allows('access-admin'))->toBe($allowed);
})->with([
    'owner' => [UserRole::Owner, true],
    'staff' => [UserRole::Staff, true],
    'customer' => [UserRole::Customer, false],
]);

test('only owners may manage the store', function (UserRole $role, bool $allowed) {
    $user = User::factory()->make(['role' => $role]);

    expect(Gate::forUser($user)->allows('manage-store'))->toBe($allowed);
})->with([
    'owner' => [UserRole::Owner, true],
    'staff' => [UserRole::Staff, false],
    'customer' => [UserRole::Customer, false],
]);
