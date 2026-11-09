<?php

namespace App\Models;

use App\Models\Concerns\HasImageFiles;
use App\Models\Concerns\Translatable;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A home page slide with a wide desktop image and an optional portrait mobile image.
 */
#[Fillable([
    'title_en',
    'title_ar',
    'subtitle_en',
    'subtitle_ar',
    'button_label_en',
    'button_label_ar',
    'link',
    'starts_at',
    'ends_at',
    'sort_order',
    'is_active',
])]
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory, HasImageFiles, Translatable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get where the banner leads in the given language.
     *
     * Store paths such as "collection/eid" are opened in the shopper's language; full https links are kept.
     */
    public function url(?string $locale = null): ?string
    {
        if (blank($this->link)) {
            return null;
        }

        if (Str::startsWith($this->link, 'https://')) {
            return $this->link;
        }

        return url(($locale ?? app()->getLocale()).'/'.ltrim($this->link, '/'));
    }

    /**
     * Determine whether the banner is showing on the home page right now.
     */
    public function isLive(): bool
    {
        return $this->is_active && $this->starts_at?->isFuture() !== true && $this->ends_at?->isPast() !== true;
    }

    /**
     * Scope the query to banners shown right now.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * Order banners as the owner arranged them.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the attributes that hold paths to stored image files.
     *
     * @return list<string>
     */
    protected function imageAttributes(): array
    {
        return ['image_path', 'mobile_image_path'];
    }
}
