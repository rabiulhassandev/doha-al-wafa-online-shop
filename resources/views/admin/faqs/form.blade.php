{{-- Shared question fields. Expects $faq. --}}
<x-admin.card title="Question and answer" description="In answers, leave a blank line between paragraphs and wrap words in **double stars** for bold.">
    <div class="grid gap-5 lg:grid-cols-2">
        <x-admin.input name="question_en" label="English question" :value="$faq->question_en" required maxlength="255" />
        <x-admin.input name="question_ar" label="Arabic question" :value="$faq->question_ar" required maxlength="255" dir="rtl" lang="ar" />
        <x-admin.textarea name="answer_en" label="English answer" :value="$faq->answer_en" rows="6" required maxlength="5000" />
        <x-admin.textarea name="answer_ar" label="Arabic answer" :value="$faq->answer_ar" rows="6" required maxlength="5000" dir="rtl" lang="ar" />
    </div>
</x-admin.card>

<x-admin.card title="Display">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="sort_order" type="number" label="Display order" :value="$faq->sort_order ?? 0" min="0" max="9999" hint="Lower numbers come first." />
        <div class="sm:pt-7">
            <x-admin.toggle name="is_published" label="Show on the FAQ page" :checked="$faq->exists ? $faq->is_published : true" />
        </div>
    </div>
</x-admin.card>
