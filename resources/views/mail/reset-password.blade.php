{{-- The link to choose a new account password. Expects $user, $url and $minutes. --}}
<x-email.layout :title="__('Choose a new password')" :footer="__('If you did not ask for this, you can ignore this email. Your password stays the same.')">
    <h1 style="margin: 0 0 8px; font-size: 20px;">{{ __('Hello :name,', ['name' => $user->name]) }}</h1>
    <p style="margin: 0 0 20px;">{{ __('Someone asked to choose a new password for your account. Use the button below. The link works for :minutes minutes.', ['minutes' => $minutes]) }}</p>

    <p style="margin: 0 0 20px;">
        <a href="{{ $url }}" style="display: inline-block; padding: 12px 24px; border-radius: 999px; background-color: #7a1e35; color: #ffffff; font-weight: bold; text-decoration: none;">{{ __('Choose a new password') }}</a>
    </p>

    <p style="margin: 0; font-size: 12px; color: #6b625a; word-break: break-all;">{{ $url }}</p>
</x-email.layout>
