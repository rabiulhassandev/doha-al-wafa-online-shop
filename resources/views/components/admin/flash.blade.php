@if (session('status'))
    <div
        x-data="{ visible: true }"
        x-show="visible"
        x-transition.opacity
        class="flex items-start gap-3 rounded-xl border border-success-700/20 bg-success-50 px-4 py-3 text-sm text-success-700"
        role="status"
    >
        <x-icon name="check" class="mt-0.5 size-5" />
        <p class="flex-1">{{ session('status') }}</p>
        <button type="button" class="-m-1 inline-flex size-8 items-center justify-center rounded-lg hover:bg-success-700/10" x-on:click="visible = false">
            <x-icon name="close" class="size-4" />
            <span class="sr-only">Dismiss</span>
        </button>
    </div>
@endif
