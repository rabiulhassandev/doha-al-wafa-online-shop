@props(['action', 'title', 'message', 'method' => 'DELETE', 'confirmLabel' => 'Delete'])

{{-- A button that asks for confirmation in a dialog, then submits a form. Avoids the browser's confirm(). --}}
<div x-data="{ open: false }" class="inline-flex">
    <button type="button" x-on:click="open = true" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-sm font-medium text-danger-700 hover:underline']) }}>
        {{ $slot }}
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center" role="dialog" aria-modal="true" x-on:keydown.escape.window="open = false">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink-900/40" x-on:click="open = false"></div>

            <div x-show="open" x-transition class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-base font-semibold">{{ $title }}</h2>
                <p class="mt-2 text-sm text-ink-500">{{ $message }}</p>

                <form method="POST" action="{{ $action }}" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @method($method)
                    <x-admin.button variant="secondary" x-on:click="open = false">Cancel</x-admin.button>
                    <x-admin.button variant="danger" type="submit">{{ $confirmLabel }}</x-admin.button>
                </form>
            </div>
        </div>
    </template>
</div>
