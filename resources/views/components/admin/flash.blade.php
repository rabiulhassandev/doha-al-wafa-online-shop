@foreach (['status' => 'success', 'error' => 'danger'] as $key => $tone)
    @if (session($key))
        <div
            x-data="{ visible: true }"
            x-show="visible"
            x-transition.opacity
            @class([
                'flex items-start gap-3 rounded-xl border px-4 py-3 text-sm',
                'border-success-700/20 bg-success-50 text-success-700' => $tone === 'success',
                'border-danger-700/20 bg-danger-50 text-danger-700' => $tone === 'danger',
            ])
            role="{{ $tone === 'danger' ? 'alert' : 'status' }}"
        >
            <x-icon :name="$tone === 'danger' ? 'alert' : 'check'" class="mt-0.5 size-5" />
            <p class="flex-1">{{ session($key) }}</p>
            <button type="button" class="-m-1 inline-flex size-8 items-center justify-center rounded-lg hover:bg-ink-900/5" x-on:click="visible = false">
                <x-icon name="close" class="size-4" />
                <span class="sr-only">Dismiss</span>
            </button>
        </div>
    @endif
@endforeach
