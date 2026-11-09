{{-- Sort menu that keeps the current filters. Expects $filters, $pageUrl, $sortOptions, $searchTerm and $idSuffix. --}}
<form method="GET" action="{{ $pageUrl }}" class="flex items-center gap-2">
    @foreach (collect($filters->toQuery())->except('sort')->put('q', $searchTerm)->filter() as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <label for="sort-{{ $idSuffix }}" class="text-sm text-ink-500">{{ __('Sort') }}</label>
    <select
        id="sort-{{ $idSuffix }}"
        name="sort"
        x-on:change="$el.form.requestSubmit()"
        class="h-10 rounded-full border border-sand-300 bg-white ps-3 pe-8 text-sm font-medium focus:border-maroon-700 focus:outline-none"
    >
        @foreach ($sortOptions as $value => $label)
            <option value="{{ $value }}" @selected($filters->sort === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="text-sm underline">{{ __('Apply') }}</button></noscript>
</form>
