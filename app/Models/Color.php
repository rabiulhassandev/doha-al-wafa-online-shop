<?php

namespace App\Models;

use App\Models\Concerns\HasImageFiles;
use Database\Factories\ColorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A shared palette color that products offer and shoppers filter by.
 */
#[Fillable(['name_en', 'name_ar', 'slug', 'hex', 'sort_order'])]
class Color extends Model
{
    /** @use HasFactory<ColorFactory> */
    use HasFactory, HasImageFiles;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Order colors as the owner arranged them.
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
        return ['swatch_path'];
    }
}
