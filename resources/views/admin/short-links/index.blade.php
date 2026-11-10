@php
    use App\Support\Money;
@endphp

<x-admin.layout title="Short links">
    <x-admin.page-header title="Short links" description="Short addresses for bios, ads, stories and influencers. Each one counts visits and remembers the orders it brought.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.short-links.create')">
                <x-icon name="plus" class="size-4" />
                Make a link
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Link</x-admin.th>
            <x-admin.th>Campaign tags</x-admin.th>
            <x-admin.th class="text-end">Visits</x-admin.th>
            <x-admin.th class="text-end">Orders</x-admin.th>
            <x-admin.th class="text-end">Sales</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($shortLinks as $shortLink)
            <tr>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $shortLink->label }}</p>
                    <div class="mt-1 flex items-center gap-2" x-data="{ copied: false }">
                        <span class="font-mono text-sm text-maroon-700 select-all">{{ $shortLink->shortUrl() }}</span>
                        <button
                            type="button"
                            class="rounded-md px-1.5 py-0.5 text-xs font-medium text-ink-500 hover:bg-sand-100 hover:text-ink-900"
                            x-on:click="navigator.clipboard.writeText(@js($shortLink->shortUrl())); copied = true; setTimeout(() => copied = false, 1500)"
                        >
                            <span x-show="! copied">Copy</span>
                            <span x-show="copied" x-cloak>Copied</span>
                        </button>
                    </div>
                    <p class="mt-1 max-w-xs truncate text-xs text-ink-500" title="{{ $shortLink->destination }}">Opens {{ $shortLink->destination }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-ink-700">
                    @if ($shortLink->utm_source || $shortLink->utm_medium)
                        <p>{{ collect([$shortLink->utm_source, $shortLink->utm_medium])->filter()->implode(' / ') }}</p>
                    @endif
                    @if ($shortLink->utm_campaign)
                        <p class="text-xs text-ink-500">Campaign: {{ $shortLink->utm_campaign }}</p>
                    @endif
                    @if ($shortLink->coupon_code)
                        <p class="text-xs text-ink-500">Applies <span class="font-mono">{{ $shortLink->coupon_code }}</span></p>
                    @endif
                </td>
                <td class="px-4 py-3 text-end tabular-nums">{{ number_format($shortLink->clicks) }}</td>
                <td class="px-4 py-3 text-end tabular-nums">
                    {{ number_format($shortLink->orders_count) }}
                    @if ($shortLink->clicks > 0 && $shortLink->orders_count > 0)
                        <p class="text-xs text-ink-500">{{ number_format($shortLink->orders_count / $shortLink->clicks * 100, 1) }}% of visits</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-end whitespace-nowrap tabular-nums">{{ Money::format((int) $shortLink->sales) }}</td>
                <td class="px-4 py-3">
                    @if ($shortLink->is_active)
                        <x-admin.badge color="success">Working</x-admin.badge>
                    @else
                        <x-admin.badge>Off</x-admin.badge>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.short-links.edit', $shortLink)"
                        :delete-url="$shortLink->orders_count === 0 ? route('admin.short-links.destroy', $shortLink) : null"
                        :delete-title="'Delete “'.$shortLink->label.'”?'"
                        delete-message="Anyone who opens it will see “page not found”."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="7" message="No short links yet. Make one for your Instagram bio, a TikTok video or an influencer.">
                <x-admin.button :href="route('admin.short-links.create')" size="sm">Make the first link</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>

    <p class="text-xs text-ink-500">Visits from link previews (WhatsApp, Instagram and others) are not counted. Orders and sales leave out cancelled orders; sales also leave out returns.</p>
</x-admin.layout>
