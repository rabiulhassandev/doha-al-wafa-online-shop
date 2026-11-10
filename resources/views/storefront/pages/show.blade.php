{{-- A page written in the admin panel. Expects $page. --}}
<x-store.layout :title="$page->translated('title')">
    <div class="page-container py-8">
        <x-store.breadcrumbs :items="[[__('Home'), route('home')], [$page->translated('title'), null]]" />

        <article class="mx-auto mt-6 max-w-2xl">
            <h1 class="font-display text-3xl sm:text-4xl">{{ $page->translated('title') }}</h1>
            <div class="rich-text mt-6 text-ink-700">
                {{ $page->bodyHtml() }}
            </div>
        </article>
    </div>
</x-store.layout>
