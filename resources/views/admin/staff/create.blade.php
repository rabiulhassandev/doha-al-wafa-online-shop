<x-admin.layout title="Add team member">
    <x-admin.page-header title="Add team member" :description="'They sign in at '.route('admin.login').' with the email and password you set.'" />

    <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-6">
        @csrf

        @include('admin.staff.form', ['member' => $member, 'roles' => $roles])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.staff.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add team member</x-admin.button>
        </div>
    </form>
</x-admin.layout>
