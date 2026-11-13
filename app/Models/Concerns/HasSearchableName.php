<?php

namespace App\Models\Concerns;

use App\Support\SearchText;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasSearchableName
{
    protected static function bootHasSearchableName(): void
    {
        static::saving(function (self $model): void {
            $model->search_name = SearchText::normalize("{$model->name_en} {$model->name_ar}");
        });
    }

    #[Scope]
    protected function matchingName(Builder $query, string $term): void
    {
        foreach (SearchText::words($term) as $word) {
            $query->whereRaw("search_name LIKE ? ESCAPE '!'", [SearchText::containsPattern($word)]);
        }
    }
}
