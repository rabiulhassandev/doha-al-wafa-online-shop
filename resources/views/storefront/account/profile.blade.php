{{-- The account's details and password. Expects $user and $phone (formatted). --}}
<x-store.account-layout :title="__('My details')">
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 sm:p-6" aria-labelledby="details-title">
            <h2 id="details-title" class="text-base font-semibold">{{ __('Your details') }}</h2>
            <form method="POST" action="{{ route('account.profile.update') }}" novalidate class="mt-5 grid gap-5">
                @csrf
                @method('PUT')
                <x-store.text-field name="name" :label="__('Full name')" :value="$user->name" autocomplete="name" maxlength="120" required />
                <x-store.text-field name="email" type="email" inputmode="email" autocomplete="email" maxlength="190" :label="__('Email')" :value="$user->email" required />
                <x-store.phone-field :label="__('Mobile number')" :value="$phone" required />
                <x-store.button type="submit" class="justify-self-start">{{ __('Save details') }}</x-store.button>
            </form>
        </section>

        <section class="rounded-2xl bg-white p-5 sm:p-6" aria-labelledby="password-title">
            <h2 id="password-title" class="text-base font-semibold">{{ __('Change password') }}</h2>
            <form method="POST" action="{{ route('account.password.update') }}" novalidate class="mt-5 grid gap-5">
                @csrf
                @method('PUT')
                @foreach ([
                    ['current_password', __('Current password'), 'current-password'],
                    ['password', __('New password'), 'new-password'],
                    ['password_confirmation', __('New password again'), 'new-password'],
                ] as [$field, $label, $autocomplete])
                    <div>
                        <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                        <input
                            id="{{ $field }}"
                            name="{{ $field }}"
                            type="password"
                            autocomplete="{{ $autocomplete }}"
                            required
                            @class([
                                'mt-1.5 block h-12 w-full rounded-xl border bg-white px-4 text-base focus:ring-2 focus:outline-none',
                                'border-danger-700 focus:ring-danger-50' => $errors->password->has($field),
                                'border-sand-300 focus:border-maroon-700 focus:ring-maroon-100' => ! $errors->password->has($field),
                            ])
                        >
                        @if ($errors->password->has($field))
                            <p class="mt-1.5 text-xs font-medium text-danger-700">{{ $errors->password->first($field) }}</p>
                        @endif
                    </div>
                @endforeach
                <x-store.button type="submit" variant="outline" class="justify-self-start">{{ __('Change password') }}</x-store.button>
            </form>
        </section>
    </div>
</x-store.account-layout>
