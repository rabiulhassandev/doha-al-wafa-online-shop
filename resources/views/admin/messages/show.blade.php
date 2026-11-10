<x-admin.layout :title="'Message from '.$contactMessage->name">
    <x-admin.page-header :title="'Message from '.$contactMessage->name" :description="'Received '.$contactMessage->created_at->format('D j M Y, H:i').' · written in '.config('shop.locales.'.$contactMessage->locale.'.label', 'English')">
        <x-slot:actions>
            @if ($contactMessage->phone)
                <x-admin.button :href="'https://wa.me/'.ltrim($contactMessage->phone, '+')" variant="secondary" target="_blank" rel="noopener">
                    <x-icon name="whatsapp" class="size-4" />
                    Reply on WhatsApp
                </x-admin.button>
                <x-admin.button :href="'tel:'.$contactMessage->phone" variant="secondary">
                    <x-icon name="phone" class="size-4" />
                    Call
                </x-admin.button>
            @endif
            @if ($contactMessage->email)
                <x-admin.button :href="'mailto:'.$contactMessage->email" variant="secondary">
                    <x-icon name="mail" class="size-4" />
                    Reply by email
                </x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card>
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            @if ($contactMessage->phone)
                <div>
                    <dt class="text-ink-500">Mobile number</dt>
                    <dd class="font-medium tabular-nums" dir="ltr">{{ $contactMessage->formattedPhone() }}</dd>
                </div>
            @endif
            @if ($contactMessage->email)
                <div>
                    <dt class="text-ink-500">Email</dt>
                    <dd class="font-medium">{{ $contactMessage->email }}</dd>
                </div>
            @endif
        </dl>
        <p class="mt-5 text-base leading-relaxed whitespace-pre-line" dir="auto">{{ $contactMessage->message }}</p>
    </x-admin.card>

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.messages.index') }}" class="text-sm text-ink-700 hover:underline">All messages</a>
        <x-admin.confirm-button :action="route('admin.messages.destroy', $contactMessage)" title="Delete this message?" message="It is removed from the inbox for everyone.">
            <x-icon name="trash" class="size-4" />
            Delete
        </x-admin.confirm-button>
    </div>
</x-admin.layout>
