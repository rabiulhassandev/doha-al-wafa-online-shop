<x-admin.layout :title="'Edit '.$member->name">
    <x-admin.page-header :title="'Edit '.$member->name" />

    <form method="POST" action="{{ route('admin.staff.update', $member) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.staff.form', ['member' => $member, 'roles' => $roles])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.staff.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
