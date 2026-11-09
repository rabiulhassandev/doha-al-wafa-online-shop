<?php

namespace App\Models;

use App\Enums\ImageFocus;
use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A product photo stored as 4:5 WebP files at several widths, e.g. "{directory}/800.webp".
 *
 * @property list<int> $widths
 */
#[Fillable(['product_color_id', 'directory', 'widths', 'focus', 'sort_order'])]
class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'widths' => 'array',
            'focus' => ImageFocus::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * Register model event listeners.
     */
    protected static function booted(): void
    {
        static::deleted(function (ProductImage $image): void {
            Storage::disk(config('shop.media_disk'))->deleteDirectory($image->directory);
        });
    }

    /**
     * Get the product color the photo belongs to.
     *
     * @return BelongsTo<ProductColor, $this>
     */
    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    /**
     * Get the URL of the smallest stored width that is at least the given width.
     */
    public function url(int $width = 800): string
    {
        $widths = collect($this->widths)->sort()->values();
        $chosen = $widths->first(fn (int $storedWidth): bool => $storedWidth >= $width) ?? $widths->last();

        return Storage::disk(config('shop.media_disk'))->url("{$this->directory}/{$chosen}.webp");
    }

    /**
     * Get the srcset attribute value listing every stored width.
     */
    public function srcset(): string
    {
        return collect($this->widths)
            ->sort()
            ->map(fn (int $width): string => Storage::disk(config('shop.media_disk'))->url("{$this->directory}/{$width}.webp")." {$width}w")
            ->implode(', ');
    }
}
