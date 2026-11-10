<x-admin.layout title="Messages">
    <x-admin.page-header title="Messages" description="Sent with the store’s contact form. Unread messages come first." />

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>From</x-admin.th>
            <x-admin.th>Message</x-admin.th>
            <x-admin.th>Received</x-admin.th>
        </x-slot:head>

        @forelse ($contactMessages as $contactMessage)
            <tr @class(['hover:bg-sand-50', 'bg-maroon-50/40' => $contactMessage->read_at === null])>
                <td class="px-4 py-3 whitespace-nowrap">
                    <a href="{{ route('admin.messages.show', $contactMessage) }}" @class(['hover:underline', 'font-semibold' => $contactMessage->read_at === null, 'font-medium' => $contactMessage->read_at !== null])>{{ $contactMessage->name }}</a>
                    @if ($contactMessage->read_at === null)
                        <x-admin.badge color="maroon" class="ms-2">New</x-admin.badge>
                    @endif
                    <p class="text-xs text-ink-500" dir="ltr">{{ $contactMessage->formattedPhone() ?? $contactMessage->email }}</p>
                </td>
                <td class="max-w-md px-4 py-3 text-ink-700">
                    <p class="truncate">{{ $contactMessage->message }}</p>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">{{ $contactMessage->created_at->format('j M, H:i') }}</td>
            </tr>
        @empty
            <x-admin.empty-row colspan="3" message="No messages yet." />
        @endforelse
    </x-admin.table>

    {{ $contactMessages->links() }}
</x-admin.layout>
