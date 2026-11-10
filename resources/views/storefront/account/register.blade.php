{{-- Creating an account. Expects $next, the page to come back to (or null). --}}
<x-store.auth-card :title="__('Create an account')" :intro="__('Follow your orders, save your address and keep your wishlist on every device.')">
    <form method="POST" action="{{ route('account.register.store') }}" novalidate class="grid gap-5">
        @csrf
        @if ($next)
            <input type="hidden" name="next" value="{{ $next }}">
        @endif

        <x-store.text-field name="name" :label="__('Full name')" autocomplete="name" maxlength="120" required />
        <x-store.text-field name="email" type="email" inputmode="email" autocomplete="email" maxlength="190" :label="__('Email')" required />
        <x-store.phone-field :label="__('Mobile number')" :hint="__('We use it to confirm your orders.')" required />
        <x-store.text-field name="password" type="password" autocomplete="new-password" :label="__('Password')" :value="''" :hint="__('At least 8 characters.')" required />
        <x-store.text-field name="password_confirmation" type="password" autocomplete="new-password" :label="__('Password again')" :value="''" required />

        <x-store.button type="submit" size="lg" class="w-full">{{ __('Create account') }}</x-store.button>
    </form>

    <x-slot:footer>
        {{ __('Already have an account?') }}
        <a href="{{ route('account.login', $next ? ['next' => $next] : []) }}" class="font-medium text-maroon-700 underline underline-offset-4">{{ __('Sign in') }}</a>
    </x-slot:footer>
</x-store.auth-card>
