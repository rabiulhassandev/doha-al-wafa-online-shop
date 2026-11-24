<?php

namespace App\Models;

use Database\Factories\ProductVideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['product_color_id', 'path', 'mime_type'])]
class ProductVideo extends Model
{
    /** @use HasFactory<ProductVideoFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(function (ProductVideo $video): void {
            Storage::disk(config('shop.media_disk'))->delete($video->path);
        });
    }

    /** @return BelongsTo<ProductColor, $this> */
    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    public function url(): string
    {
        return Storage::disk(config('shop.media_disk'))->url($this->path);
    }
}
