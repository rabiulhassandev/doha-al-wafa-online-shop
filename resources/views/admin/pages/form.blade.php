{{-- Shared page fields. Expects $page. --}}
<x-admin.card title="Page">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="title_en" label="English title" :value="$page->title_en" required maxlength="150" placeholder="Exchange and returns" />
        <x-admin.input name="title_ar" label="Arabic title" :value="$page->title_ar" required maxlength="150" dir="rtl" lang="ar" placeholder="الاستبدال والإرجاع" />
        <x-admin.input name="slug" label="Web address" :value="$page->slug" required maxlength="80" prefix="/pages/" placeholder="exchange-and-returns" hint="Lowercase letters, digits and dashes. Changing it breaks links people already shared." />
        <x-admin.input name="sort_order" type="number" label="Display order" :value="$page->sort_order ?? 0" min="0" max="9999" hint="Lower numbers come first in the footer." />
    </div>
</x-admin.card>

<x-admin.card title="Text" description="Leave a blank line between paragraphs. Start a line with ## for a heading, or with - for a list. Wrap words in **double stars** for bold.">
    <div class="grid gap-5 lg:grid-cols-2">
        <x-admin.textarea name="body_en" label="English text" :value="$page->body_en" rows="18" maxlength="20000" />
        <x-admin.textarea name="body_ar" label="Arabic text" :value="$page->body_ar" rows="18" maxlength="20000" dir="rtl" lang="ar" />
    </div>
</x-admin.card>

<x-admin.card>
    <x-admin.toggle name="is_published" label="Show this page in the store" :checked="$page->is_published" hint="Hidden pages can be finished first. Shown pages are linked from the footer." />
</x-admin.card>
