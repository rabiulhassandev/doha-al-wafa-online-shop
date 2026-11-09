<x-admin.guest-layout title="Sign in">
    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
        @csrf

        <x-admin.input name="email" type="email" label="Email" autocomplete="username" required autofocus />
        <x-admin.input name="password" type="password" label="Password" autocomplete="current-password" required />
        <x-admin.checkbox name="remember" label="Keep me signed in on this device" />

        <x-admin.button type="submit" class="w-full">Sign in</x-admin.button>
    </form>
</x-admin.guest-layout>
