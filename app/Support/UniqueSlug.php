<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class UniqueSlug
{
    /**
     * Build a URL slug from the given text that no other record of the model uses.
     *
     * A clash gets a numeric suffix: "eid", "eid-2", "eid-3".
     *
     * @param  class-string<Model>  $model
     */
    public static function generate(string $model, string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;

        for ($suffix = 2; self::isTaken($model, $slug, $ignoreId); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    /**
     * Determine whether another record of the model already uses the slug.
     *
     * @param  class-string<Model>  $model
     */
    private static function isTaken(string $model, string $slug, ?int $ignoreId): bool
    {
        return $model::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
