<x-admin.layout :title="'Adjust stock · '.$variant->sku">
    <div>
        <a href="{{ route('admin.inventory.index') }}" class="text-sm text-ink-500 hover:underline">← Inventory</a>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $variant->product->name_en }}</h1>
        <p class="mt-1 flex items-center gap-2 text-sm text-ink-500">
            @include('admin.colors.swatch', ['color' => $variant->productColor->color, 'sizeClass' => 'size-5'])
            {{ $variant->productColor->nameEn() }} · Size {{ $variant->size->name_en }} · <span class="font-mono text-xs">{{ $variant->sku }}</span>
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
        <x-admin.card title="Change stock">
            <p class="text-sm text-ink-500">In stock now</p>
            <p class="text-4xl font-semibold tabular-nums">{{ $variant->stock }}</p>

            <form method="POST" action="{{ route('admin.inventory.adjustments.store', $variant) }}" class="mt-6 space-y-5">
                @csrf

                <fieldset>
                    <legend class="text-sm font-medium">What happened?</legend>
                    <div class="mt-2 space-y-2">
                        @foreach ($reasonOptions as $value => $label)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-sand-300 bg-white p-3 text-sm has-checked:border-maroon-700 has-checked:bg-maroon-50">
                                <input type="radio" name="reason" value="{{ $value }}" @checked(old('reason', 'received') === $value) class="mt-0.5 accent-maroon-700">
                                <span>
                                    <span class="block font-medium">{{ $label }}</span>
                                    <span class="block text-xs text-ink-500">
                                        @switch($value)
                                            @case('received') Adds the quantity to the stock. @break
                                            @case('damaged') Removes the quantity from the stock. @break
                                            @default Replaces the stock with the number you counted.
                                        @endswitch
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('reason')
                        <p class="mt-1.5 text-xs font-medium text-danger-700">{{ $message }}</p>
                    @enderror
                </fieldset>

                <x-admin.input name="quantity" type="number" label="Quantity" min="0" max="99999" inputmode="numeric" required />
                <x-admin.input name="note" label="Note (optional)" maxlength="200" placeholder="Supplier delivery, stain on sleeve…" />

                <x-admin.button type="submit" class="w-full">Save</x-admin.button>
            </form>
        </x-admin.card>

        <x-admin.card title="Recent changes">
            @if ($movements->isEmpty())
                <p class="text-sm text-ink-500">No changes yet.</p>
            @else
                <ul class="divide-y divide-sand-200">
                    @foreach ($movements as $movement)
                        <li class="flex items-start justify-between gap-4 py-3 text-sm">
                            <div>
                                <p class="font-medium">{{ $movement->reason->label() }}</p>
                                <p class="text-xs text-ink-500">
                                    {{ $movement->created_at->format('j M Y, H:i') }}
                                    @if ($movement->user)
                                        · {{ $movement->user->name }}
                                    @endif
                                </p>
                                @if ($movement->note)
                                    <p class="mt-1 text-ink-700">{{ $movement->note }}</p>
                                @endif
                            </div>
                            <div class="text-end tabular-nums">
                                <p @class(['font-semibold', 'text-success-700' => $movement->change > 0, 'text-danger-700' => $movement->change < 0])>
                                    {{ $movement->change > 0 ? '+' : '' }}{{ $movement->change }}
                                </p>
                                <p class="text-xs text-ink-500">now {{ $movement->stock_after }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    </div>
</x-admin.layout>
