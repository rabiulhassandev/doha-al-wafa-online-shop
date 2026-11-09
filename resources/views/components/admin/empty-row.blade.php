@props(['colspan', 'message'])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-12 text-center">
        <p class="text-sm text-ink-500">{{ $message }}</p>
        @if ($slot->isNotEmpty())
            <div class="mt-4">{{ $slot }}</div>
        @endif
    </td>
</tr>
