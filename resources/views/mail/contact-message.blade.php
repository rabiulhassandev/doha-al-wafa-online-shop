{{-- A contact form message, for the store team. Expects $contactMessage. --}}
<x-email.layout :title="'Message from '.$contactMessage->name" :preheader="\Illuminate\Support\Str::limit($contactMessage->message, 90)">
    <h1 style="margin: 0 0 4px; font-size: 20px;">Message from {{ $contactMessage->name }}</h1>
    <p style="margin: 0 0 16px; color: #6b625a;">
        @if ($contactMessage->phone)
            <a href="tel:{{ $contactMessage->phone }}" style="color: #7a1e35;">{{ $contactMessage->formattedPhone() }}</a>
            · <a href="https://wa.me/{{ ltrim($contactMessage->phone, '+') }}" style="color: #7a1e35;">WhatsApp</a>
        @endif
        @if ($contactMessage->email)
            @if ($contactMessage->phone) · @endif
            <a href="mailto:{{ $contactMessage->email }}" style="color: #7a1e35;">{{ $contactMessage->email }}</a>
        @endif
    </p>

    <p style="margin: 0; padding: 12px 16px; border-radius: 12px; background-color: #f5efe6; white-space: pre-line;">{{ $contactMessage->message }}</p>

    <p style="margin: 24px 0 0;">
        <a href="{{ route('admin.messages.show', $contactMessage) }}" style="display: inline-block; padding: 12px 24px; border-radius: 999px; background-color: #7a1e35; color: #ffffff; font-weight: bold; text-decoration: none;">Open the inbox</a>
    </p>
</x-email.layout>
