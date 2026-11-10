<x-admin.layout title="FAQ">
    <x-admin.page-header title="Frequently asked questions" description="Shown on the store’s FAQ page, in display order.">
        <x-slot:actions>
            <x-admin.button :href="route('pages.faq', ['locale' => 'en'])" variant="secondary" target="_blank">
                <x-icon name="external" class="size-4" />
                View in the store
            </x-admin.button>
            <x-admin.button :href="route('admin.faqs.create')">
                <x-icon name="plus" class="size-4" />
                Add question
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th class="w-16">Order</x-admin.th>
            <x-admin.th>Question</x-admin.th>
            <x-admin.th>Status</x-admin.th>
            <x-admin.th><span class="sr-only">Actions</span></x-admin.th>
        </x-slot:head>

        @forelse ($faqs as $faq)
            <tr>
                <td class="px-4 py-3 text-ink-500 tabular-nums">{{ $faq->sort_order }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $faq->question_en }}</p>
                    <p class="text-ink-500" lang="ar" dir="rtl">{{ $faq->question_ar }}</p>
                </td>
                <td class="px-4 py-3">
                    <x-admin.badge :color="$faq->is_published ? 'success' : 'neutral'">{{ $faq->is_published ? 'Shown' : 'Hidden' }}</x-admin.badge>
                </td>
                <td class="px-4 py-3">
                    <x-admin.row-actions
                        :edit-url="route('admin.faqs.edit', $faq)"
                        :delete-url="route('admin.faqs.destroy', $faq)"
                        delete-title="Delete this question?"
                        delete-message="It disappears from the FAQ page in both languages."
                    />
                </td>
            </tr>
        @empty
            <x-admin.empty-row colspan="4" message="No questions yet.">
                <x-admin.button :href="route('admin.faqs.create')" size="sm">Add the first question</x-admin.button>
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</x-admin.layout>
