{{-- Signing in. Expects $next, the page to come back to (or null). --}}
<x-store.auth-card :title="__('Sign in')" :intro="__('See your orders and check out faster. You can always order without an account.')">
    <form method="POST" action="{{ route('account.login.store') }}" novalidate class="grid gap-5">
        @csrf
        @if ($next)
            <input type="hidden" name="next" value="{{ $next }}">
        @endif

        <x-store.text-field name="email" type="email" inputmode="email" autocomplete="email" :label="__('Email')" required />
        <div>
            <x-store.text-field name="password" type="password" autocomplete="current-password" :label="__('Password')" :value="''" required />
            <a href="{{ route('account.password.request') }}" class="mt-2 inline-block text-sm font-medium text-maroon-700 underline underline-offset-4">{{ __('Forgot your password?') }}</a>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 accent-maroon-700">
            {{ __('Keep me signed in on this device') }}
        </label>

        <x-store.button type="submit" size="lg" class="w-full">{{ __('Sign in') }}</x-store.button>
    </form>

    <x-slot:footer>
        {{ __('New here?') }}
        <a href="{{ route('account.register', $next ? ['next' => $next] : []) }}" class="font-medium text-maroon-700 underline underline-offset-4">{{ __('Create an account') }}</a>
    </x-slot:footer>
</x-store.auth-card>
