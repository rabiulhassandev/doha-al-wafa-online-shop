{{-- Morning stock summary for the store team. Expects $variants and $threshold. --}}
<x-email.layout title="Stock check" :preheader="$variants->count().' colors and sizes need restocking'">
    <h1 style="margin: 0 0 4px; font-size: 20px;">Stock check</h1>
    <p style="margin: 0 0 16px; color: #6b625a;">Colors and sizes on sale with {{ $threshold }} or fewer left, sold out first.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; border-top: 1px solid #eadfcf;">
        @foreach ($variants as $variant)
            <tr>
                <td style="padding: 10px 8px 10px 0; border-bottom: 1px solid #eadfcf;">
                    <strong>{{ $variant->product->name_en }}</strong><br>
                    <span style="color: #6b625a;">{{ $variant->productColor->nameEn() }} · Size {{ $variant->size->name_en }} · SKU {{ $variant->sku }}</span>
                </td>
                <td style="padding: 10px 0; border-bottom: 1px solid #eadfcf; text-align: right; white-space: nowrap; font-weight: bold; color: {{ $variant->stock === 0 ? '#b42318' : '#1e1a17' }};">
                    {{ $variant->stock === 0 ? 'Sold out' : $variant->stock.' left' }}
                </td>
            </tr>
        @endforeach
    </table>

    <p style="margin: 24px 0 0;">
        <a href="{{ route('admin.inventory.index', ['stock' => 'low']) }}" style="display: inline-block; padding: 12px 24px; border-radius: 999px; background-color: #7a1e35; color: #ffffff; font-weight: bold; text-decoration: none;">Open inventory</a>
    </p>
</x-email.layout>
