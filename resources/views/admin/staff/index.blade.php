<x-admin.layout title="Team">
    <x-admin.page-header title="Team" description="People who can sign in to this admin panel.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.staff.create')">
                <x-icon name="plus" class="size-4" />
                Add team member
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Name</x-admin.th>
            <x-admin.th>Email</x-admin.th>
            <x-admin.th>Role</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @foreach ($staff as $member)
            <tr>
                <td class="px-4 py-3 font-medium whitespace-nowrap">
                    {{ $member->name }}
                    @if ($member->is(auth()->user()))
                        <span class="ms-1 text-xs font-normal text-ink-500">(you)</span>
                    @endif
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">{{ $member->email }}</td>
                <td class="px-4 py-3">
                    <x-admin.badge :color="$member->isOwner() ? 'maroon' : 'neutral'">{{ $member->role->label() }}</x-admin.badge>
                </td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.staff.edit', $member)"
                        :delete-url="auth()->user()->can('delete', $member) ? route('admin.staff.destroy', $member) : null"
                        :delete-title="'Remove '.$member->name.'?'"
                        delete-message="They will no longer be able to sign in to the admin panel."
                        delete-label="Remove"
                    />
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    {{ $staff->links() }}
</x-admin.layout>
