<x-admin.layout title="Subscribers">
    <x-admin.page-header title="Subscribers" :description="$total.' people agreed to receive news and offers. Remove anyone who asks to stop.'">
        <x-slot:actions>
            <x-admin.button :href="route('admin.subscribers.export')" variant="secondary">Download CSV</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="GET" action="{{ route('admin.subscribers.index') }}" class="grid gap-3 rounded-2xl border border-sand-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
        <x-admin.input name="search" type="search" label="Search" :value="$search" placeholder="WhatsApp number or email" />
        <div class="flex gap-2">
            <x-admin.button type="submit" variant="secondary">Search</x-admin.button>
            @if ($search !== '')
                <x-admin.button :href="route('admin.subscribers.index')" variant="ghost">Clear</x-admin.button>
            @endif
        </div>
    </form>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>WhatsApp number or email</x-admin.th>
            <x-admin.th>Language</x-admin.th>
            <x-admin.th>Joined</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($subscribers as $subscriber)
            <tr>
                <td class="px-4 py-3 font-medium" dir="ltr">{{ $subscriber->phone ? \App\Support\QatarPhone::tryParse($subscriber->phone)?->formatted() : $subscriber->email }}</td>
                <td class="px-4 py-3 text-ink-700">{{ config('shop.locales.'.$subscriber->locale.'.label', $subscriber->locale) }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-ink-700">{{ $subscriber->created_at->format('j M Y') }}</td>
                <td class="px-4 py-3 text-end">
                    <x-admin.confirm-button
                        :action="route('admin.subscribers.destroy', $subscriber)"
                        title="Remove this subscriber?"
                        message="They will not be in the next download. Do this when someone asks to stop receiving offers."
                        confirm-label="Remove"
                    >
                        Remove
                    </x-admin.confirm-button>
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="4" :message="$search !== '' ? 'Nobody matches.' : 'Nobody has joined yet. The sign-up form is at the bottom of every store page.'" />
        @endforelse
    </x-admin.table>

    {{ $subscribers->links() }}
</x-admin.layout>
