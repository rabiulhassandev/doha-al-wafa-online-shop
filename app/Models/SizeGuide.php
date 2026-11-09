<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Database\Factories\SizeGuideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A measurement table shown to shoppers. Values are stored in centimeters.
 *
 * @property list<array{en: string, ar: string}> $columns
 * @property list<array{size: string, values: list<float|null>}> $rows
 */
#[Fillable(['name', 'columns', 'rows', 'notes_en', 'notes_ar'])]
class SizeGuide extends Model
{
    /** @use HasFactory<SizeGuideFactory> */
    use HasFactory, Translatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'rows' => 'array',
        ];
    }
}
