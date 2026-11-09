<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('index', function () {
    it('lists team members but not customers', function () {
        $owner = User::factory()->owner()->create(['name' => 'Aisha Owner']);
        User::factory()->staff()->create(['name' => 'Noura Staff']);
        User::factory()->create(['name' => 'Customer Person']);

        $this->actingAs($owner)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Aisha Owner')
            ->assertSee('Noura Staff')
            ->assertDontSee('Customer Person');
    });

    it('forbids staff', function () {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.staff.index'))
            ->assertForbidden();
    });

    it('escapes team member names', function () {
        $owner = User::factory()->owner()->create(['name' => '<b>Owner</b>']);

        $this->actingAs($owner)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('&lt;b&gt;Owner&lt;/b&gt;', false)
            ->assertDontSee('<b>Owner</b>', false);
    });
});

describe('create', function () {
    it('preselects the staff role', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.staff.create'))
            ->assertOk()
            ->assertSee('<option value="staff" selected>', false);
    });
});

describe('store', function () {
    it('adds a team member who can sign in', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->post(route('admin.staff.store'), [
                'name' => 'Mariam',
                'email' => 'mariam@example.com',
                'role' => 'staff',
                'password' => 'secret12345',
                'password_confirmation' => 'secret12345',
            ])
            ->assertRedirectToRoute('admin.staff.index')
            ->assertSessionHas('status', 'Mariam can now sign in to the admin panel.');

        $member = User::query()->where('email', 'mariam@example.com')->sole();
        expect($member->role)->toBe(UserRole::Staff);
        expect(Hash::check('secret12345', $member->password))->toBeTrue();
    });

    it('rejects the customer role', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->post(route('admin.staff.store'), [
                'name' => 'Mariam',
                'email' => 'mariam@example.com',
                'role' => 'customer',
                'password' => 'secret12345',
                'password_confirmation' => 'secret12345',
            ])
            ->assertSessionHasErrors(['role' => 'The selected role is invalid.']);

        $this->assertDatabaseMissing('users', ['email' => 'mariam@example.com']);
    });

    it('rejects an email that is already in use', function () {
        User::factory()->create(['email' => 'mariam@example.com']);

        $this->actingAs(User::factory()->owner()->create())
            ->post(route('admin.staff.store'), [
                'name' => 'Mariam',
                'email' => 'mariam@example.com',
                'role' => 'staff',
                'password' => 'secret12345',
                'password_confirmation' => 'secret12345',
            ])
            ->assertSessionHasErrors(['email' => 'The email has already been taken.']);
    });

    it('rejects a password shorter than 10 characters', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->post(route('admin.staff.store'), [
                'name' => 'Mariam',
                'email' => 'mariam@example.com',
                'role' => 'staff',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors(['password' => 'The password field must be at least 10 characters.']);
    });

    it('requires the name, email, role and password', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->post(route('admin.staff.store'))
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);
    });
});

describe('update', function () {
    it('updates a team member and keeps the password when left empty', function () {
        $member = User::factory()->staff()->create();

        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.staff.update', $member), [
                'name' => 'Mariam Al-Thani',
                'email' => 'mariam@example.com',
                'role' => 'owner',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirectToRoute('admin.staff.index');

        $member->refresh();
        expect($member->name)->toBe('Mariam Al-Thani');
        expect($member->email)->toBe('mariam@example.com');
        expect($member->role)->toBe(UserRole::Owner);
        expect(Hash::check('password', $member->password))->toBeTrue();
    });

    it('changes the password when a new one is entered', function () {
        $member = User::factory()->staff()->create();

        $this->actingAs(User::factory()->owner()->create())
            ->put(route('admin.staff.update', $member), [
                'name' => $member->name,
                'email' => $member->email,
                'role' => 'staff',
                'password' => 'new-secret-2026',
                'password_confirmation' => 'new-secret-2026',
            ])
            ->assertRedirectToRoute('admin.staff.index');

        expect(Hash::check('new-secret-2026', $member->refresh()->password))->toBeTrue();
    });

    it('stops owners from changing their own role', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->put(route('admin.staff.update', $owner), [
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => 'staff',
            ])
            ->assertSessionHasErrors(['role' => "You can't change your own role."]);

        expect($owner->refresh()->role)->toBe(UserRole::Owner);
    });

    it('returns 404 for customer accounts', function () {
        $customer = User::factory()->create();

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.staff.edit', $customer))
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('removes another team member', function () {
        $member = User::factory()->staff()->create(['name' => 'Mariam']);

        $this->actingAs(User::factory()->owner()->create())
            ->delete(route('admin.staff.destroy', $member))
            ->assertRedirectToRoute('admin.staff.index')
            ->assertSessionHas('status', 'Mariam was removed.');

        $this->assertModelMissing($member);
    });

    it('forbids owners from removing themselves', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->delete(route('admin.staff.destroy', $owner))
            ->assertForbidden();

        $this->assertModelExists($owner);
    });
});
