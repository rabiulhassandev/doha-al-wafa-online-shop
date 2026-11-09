<?php

namespace App\Models;

use App\Enums\CollectionStatus;
use App\Models\Concerns\HasImageFiles;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A curated group of products for a campaign, such as "Eid" or "New arrivals".
 */
#[Fillable([
    'name_en',
    'name_ar',
    'slug',
    'description_en',
    'description_ar',
    'starts_at',
    'ends_at',
    'sort_order',
    'is_active',
])]
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory, HasImageFiles;

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
     * Get whether the collection is currently shown in the store.
     */
    public function status(): CollectionStatus
    {
        return match (true) {
            ! $this->is_active => CollectionStatus::Hidden,
            $this->starts_at?->isFuture() === true => CollectionStatus::Scheduled,
            $this->ends_at?->isPast() === true => CollectionStatus::Ended,
            default => CollectionStatus::Live,
        };
    }

    /**
     * Order collections as the owner arranged them.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name_en')->orderBy('id');
    }

    /**
     * Get the attributes that hold paths to stored image files.
     *
     * @return list<string>
     */
    protected function imageAttributes(): array
    {
        return ['banner_path'];
    }
}
