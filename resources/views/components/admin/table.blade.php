<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-2xl border border-sand-200 bg-white shadow-sm']) }}>
    <table class="min-w-full divide-y divide-sand-200 text-sm">
        <thead class="bg-sand-50 text-xs font-medium text-ink-500">
            <tr>
                {{ $head }}
            </tr>
        </thead>
        <tbody class="divide-y divide-sand-200">
            {{ $slot }}
        </tbody>
    </table>
</div>
