{{-- Asking for a link to choose a new password. --}}
<x-store.auth-card :title="__('Forgot your password?')" :intro="__('Enter the email of your account. We will send you a link to choose a new password.')">
    <form method="POST" action="{{ route('account.password.email') }}" novalidate class="grid gap-5">
        @csrf
        <x-store.text-field name="email" type="email" inputmode="email" autocomplete="email" :label="__('Email')" required />
        <x-store.button type="submit" size="lg" class="w-full">{{ __('Send the link') }}</x-store.button>
    </form>

    <x-slot:footer>
        <a href="{{ route('account.login') }}" class="font-medium text-maroon-700 underline underline-offset-4">{{ __('Back to sign in') }}</a>
    </x-slot:footer>
</x-store.auth-card>
