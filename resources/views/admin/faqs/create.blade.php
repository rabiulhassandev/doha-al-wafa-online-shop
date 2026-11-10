<x-admin.layout title="Add question">
    <x-admin.page-header title="Add question" />

    <form method="POST" action="{{ route('admin.faqs.store') }}" class="space-y-6">
        @csrf

        @include('admin.faqs.form', ['faq' => $faq])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.faqs.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Add question</x-admin.button>
        </div>
    </form>
</x-admin.layout>
