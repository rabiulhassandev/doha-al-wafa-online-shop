<?php

use App\Models\User;
use App\Policies\UserPolicy;

test('owners may remove other team members', function () {
    $owner = User::factory()->owner()->create();
    $staff = User::factory()->staff()->create();

    expect((new UserPolicy)->delete($owner, $staff))->toBeTrue();
});

test('owners may not remove themselves', function () {
    $owner = User::factory()->owner()->create();

    expect((new UserPolicy)->delete($owner, $owner))->toBeFalse();
});

test('staff may not remove team members', function () {
    $staff = User::factory()->staff()->create();
    $otherStaff = User::factory()->staff()->create();

    expect((new UserPolicy)->delete($staff, $otherStaff))->toBeFalse();
});
