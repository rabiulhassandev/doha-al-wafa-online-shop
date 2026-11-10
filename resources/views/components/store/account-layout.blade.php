@props(['title'])

{{-- Pages of the signed-in shopper's account, with the account menu. --}}
@php
    $tabs = [
        ['route' => 'account.orders', 'active' => 'account.orders*', 'label' => __('My orders')],
        ['route' => 'account.addresses.index', 'active' => 'account.addresses.*', 'label' => __('Addresses')],
        ['route' => 'account.profile.edit', 'active' => 'account.profile.*', 'label' => __('My details')],
        ['route' => 'wishlist', 'active' => 'wishlist', 'label' => __('Wishlist')],
    ];
@endphp

<x-store.layout :title="$title">
    @push('head')
        <meta name="robots" content="noindex">
    @endpush

    <div class="page-container py-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm text-ink-500">{{ __('Hello, :name', ['name' => auth()->user()->name]) }}</p>
                <h1 class="font-display text-3xl sm:text-4xl">{{ $title }}</h1>
            </div>
            <form method="POST" action="{{ route('account.logout') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-ink-700 underline underline-offset-4 hover:text-maroon-700">{{ __('Sign out') }}</button>
            </form>
        </div>

        <nav class="-mx-4 mt-5 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="{{ __('My account') }}">
            <ul class="flex gap-2">
                @foreach ($tabs as $tab)
                    <li>
                        <a
                            href="{{ route($tab['route']) }}"
                            @if (request()->routeIs($tab['active'])) aria-current="page" @endif
                            @class([
                                'inline-flex h-10 items-center rounded-full border px-4 text-sm font-medium whitespace-nowrap',
                                'border-ink-900 bg-ink-900 text-white' => request()->routeIs($tab['active']),
                                'border-sand-300 bg-white text-ink-700 hover:border-ink-900/40' => ! request()->routeIs($tab['active']),
                            ])
                        >{{ $tab['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if (session('account_status'))
            <p class="mt-5 flex items-center gap-2 rounded-xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700" role="status">
                <x-icon name="check" class="size-5" />
                {{ session('account_status') }}
            </p>
        @endif

        <div class="mt-6">
            {{ $slot }}
        </div>
    </div>
</x-store.layout>
