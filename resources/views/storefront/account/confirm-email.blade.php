<x-store.account-layout :title="__('Confirm your new email address')">
    <form method="POST" action="{{ request()->getRequestUri() }}" class="grid gap-5 rounded-2xl bg-white p-5 sm:p-6">
        @csrf
        <x-store.button type="submit" class="justify-self-start">{{ __('Confirm your new email address') }}</x-store.button>
    </form>
</x-store.account-layout>
