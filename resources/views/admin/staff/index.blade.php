<x-admin.layout title="Team">
    <x-admin.page-header title="Team" description="People who can sign in to this admin panel.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.staff.create')">
                <x-icon name="plus" class="size-4" />
                Add team member
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="overflow-x-auto rounded-2xl border border-sand-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-sand-200 text-sm">
            <thead class="bg-sand-50 text-start text-xs font-medium text-ink-500">
                <tr>
                    <th scope="col" class="px-4 py-3 text-start">Name</th>
                    <th scope="col" class="px-4 py-3 text-start">Email</th>
                    <th scope="col" class="px-4 py-3 text-start">Role</th>
                    <th scope="col" class="px-4 py-3 text-end"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sand-200">
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
                            <div class="flex items-center justify-end gap-4">
                                <a href="{{ route('admin.staff.edit', $member) }}" class="inline-flex items-center gap-1.5 font-medium text-ink-900 hover:underline">
                                    <x-icon name="pencil" class="size-4" />
                                    Edit
                                </a>
                                @can('delete', $member)
                                    <x-admin.confirm-button
                                        :action="route('admin.staff.destroy', $member)"
                                        :title="'Remove '.$member->name.'?'"
                                        message="They will no longer be able to sign in to the admin panel."
                                        confirm-label="Remove"
                                    >
                                        <x-icon name="trash" class="size-4" />
                                        Remove
                                    </x-admin.confirm-button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $staff->links() }}
</x-admin.layout>
