@props(['editUrl', 'deleteUrl' => null, 'deleteTitle' => 'Delete this item?', 'deleteMessage' => 'This cannot be undone.', 'deleteLabel' => 'Delete'])

<div class="flex items-center justify-end gap-4 whitespace-nowrap">
    <a href="{{ $editUrl }}" class="inline-flex items-center gap-1.5 font-medium text-ink-900 hover:underline">
        <x-icon name="pencil" class="size-4" />
        Edit
    </a>

    @if ($deleteUrl)
        <x-admin.confirm-button :action="$deleteUrl" :title="$deleteTitle" :message="$deleteMessage" :confirm-label="$deleteLabel">
            <x-icon name="trash" class="size-4" />
            {{ $deleteLabel }}
        </x-admin.confirm-button>
    @endif
</div>
