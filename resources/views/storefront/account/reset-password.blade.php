{{-- Choosing a new password from the emailed link. Expects $token and $email. --}}
<x-store.auth-card :title="__('Choose a new password')">
    <form method="POST" action="{{ route('account.password.store') }}" novalidate class="grid gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-store.text-field name="email" type="email" inputmode="email" autocomplete="email" :label="__('Email')" :value="$email" required />
        <x-store.text-field name="password" type="password" autocomplete="new-password" :label="__('New password')" :value="''" :hint="__('At least 8 characters.')" required />
        <x-store.text-field name="password_confirmation" type="password" autocomplete="new-password" :label="__('New password again')" :value="''" required />

        <x-store.button type="submit" size="lg" class="w-full">{{ __('Save the new password') }}</x-store.button>
    </form>

    <x-slot:footer>
        <a href="{{ route('account.password.request') }}" class="font-medium text-maroon-700 underline underline-offset-4">{{ __('Ask for a new link') }}</a>
    </x-slot:footer>
</x-store.auth-card>
