<x-admin.layout title="Edit question">
    <x-admin.page-header title="Edit question" />

    <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.faqs.form', ['faq' => $faq])

        <div class="flex justify-end gap-3">
            <x-admin.button :href="route('admin.faqs.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">Save changes</x-admin.button>
        </div>
    </form>
</x-admin.layout>
