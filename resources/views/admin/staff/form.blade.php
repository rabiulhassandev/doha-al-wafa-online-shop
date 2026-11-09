{{-- Shared fields for adding and editing a team member. Expects $member and $roles. --}}
@php($editingSelf = $member->exists && $member->is(auth()->user()))

<x-admin.card title="Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="name" label="Name" :value="$member->name" required autocomplete="off" />
        <x-admin.input name="email" type="email" label="Email" :value="$member->email" required autocomplete="off" />

        @if ($editingSelf)
            <input type="hidden" name="role" value="{{ $member->role->value }}">
            <div>
                <p class="text-sm font-medium">Role</p>
                <p class="mt-1.5 flex h-11 items-center text-sm text-ink-700">{{ $member->role->label() }}</p>
                <p class="mt-1.5 text-xs text-ink-500">You can't change your own role.</p>
            </div>
        @else
            <x-admin.select
                name="role"
                label="Role"
                :options="collect($roles)->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all()"
                :value="$member->role->value"
                hint="Owners can also change settings and manage the team."
            />
        @endif
    </div>
</x-admin.card>

<x-admin.card title="Password" :description="$member->exists ? 'Leave empty to keep the current password.' : 'At least 10 characters, with letters and numbers.'">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="password" type="password" label="Password" autocomplete="new-password" :required="! $member->exists" />
        <x-admin.input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" :required="! $member->exists" />
    </div>
</x-admin.card>
