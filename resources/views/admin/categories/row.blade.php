{{-- One category row. Expects $category and $isChild. --}}
<tr>
    <td class="px-4 py-3">
        @if ($category->image_path)
            <img src="{{ $category->imageUrl('image_path') }}" alt="" class="size-10 rounded-lg object-cover">
        @else
            <span class="flex size-10 items-center justify-center rounded-lg bg-sand-100 text-ink-300">
                <x-icon name="image" class="size-5" />
            </span>
        @endif
    </td>
    <td class="px-4 py-3">
        <div @class(['flex items-start gap-2', 'ps-6' => $isChild])>
            @if ($isChild)
                <span class="text-ink-300" aria-hidden="true">↳</span>
            @endif
            <div>
                <p class="font-medium">{{ $category->name_en }}</p>
                <p class="text-ink-500" lang="ar">{{ $category->name_ar }}</p>
            </div>
        </div>
    </td>
    <td class="px-4 py-3 text-ink-500">{{ $category->slug }}</td>
    <td class="px-4 py-3 tabular-nums">{{ $category->sort_order }}</td>
    <td class="px-4 py-3">
        <x-admin.badge :color="$category->is_active ? 'success' : 'neutral'">{{ $category->is_active ? 'Visible' : 'Hidden' }}</x-admin.badge>
    </td>
    <td class="px-4 py-3">
        <x-admin.row-actions
            :edit-url="route('admin.categories.edit', $category)"
            :delete-url="route('admin.categories.destroy', $category)"
            :delete-title="'Delete “'.$category->name_en.'”?'"
            delete-message="Shoppers will no longer see this category."
        />
    </td>
</tr>
