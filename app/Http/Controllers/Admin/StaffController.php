<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class StaffController extends Controller
{
    /**
     * List the admin team members.
     */
    public function index(): View
    {
        $staff = User::query()
            ->staff()
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.staff.index', ['staff' => $staff]);
    }

    /**
     * Show the form for adding a team member.
     */
    public function create(): View
    {
        $member = new User;
        $member->role = UserRole::Staff;

        return view('admin.staff.create', [
            'member' => $member,
            'roles' => UserRole::staffRoles(),
        ]);
    }

    /**
     * Add a team member.
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $member = new User($request->safe()->only(['name', 'email', 'password']));
        $member->role = $request->enum('role', UserRole::class);
        $member->save();

        return to_route('admin.staff.index')->with('status', "{$member->name} can now sign in to the admin panel.");
    }

    /**
     * Show the form for editing a team member.
     */
    public function edit(User $staff): View
    {
        return view('admin.staff.edit', [
            'member' => $staff,
            'roles' => UserRole::staffRoles(),
        ]);
    }

    /**
     * Update a team member.
     */
    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        $staff->fill($request->safe()->only(['name', 'email']));
        $staff->role = $request->enum('role', UserRole::class);

        if ($request->filled('password')) {
            $staff->password = $request->validated('password');
        }

        $staff->save();

        return to_route('admin.staff.index')->with('status', "{$staff->name} was updated.");
    }

    /**
     * Remove a team member's admin account.
     */
    public function destroy(User $staff): RedirectResponse
    {
        Gate::authorize('delete', $staff);

        $staff->delete();

        return to_route('admin.staff.index')->with('status', "{$staff->name} was removed.");
    }
}
