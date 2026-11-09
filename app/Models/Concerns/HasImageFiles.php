<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Removes stored image files when a model replaces them or is deleted.
 */
trait HasImageFiles
{
    /**
     * Get the attributes that hold paths to stored image files.
     *
     * @return list<string>
     */
    abstract protected function imageAttributes(): array;

    /**
     * Register the file cleanup listeners.
     */
    protected static function bootHasImageFiles(): void
    {
        static::updated(function (Model $model): void {
            foreach ($model->imageAttributes() as $attribute) {
                if ($model->wasChanged($attribute)) {
                    static::deleteImageFile($model->getRawOriginal($attribute));
                }
            }
        });

        static::deleted(function (Model $model): void {
            foreach ($model->imageAttributes() as $attribute) {
                static::deleteImageFile($model->getAttribute($attribute));
            }
        });
    }

    /**
     * Get the public URL of a stored image.
     */
    public function imageUrl(string $attribute): ?string
    {
        $path = $this->getAttribute($attribute);

        return $path ? Storage::disk(config('shop.media_disk'))->url($path) : null;
    }

    /**
     * Delete a stored image file if there is one.
     */
    private static function deleteImageFile(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk(config('shop.media_disk'))->delete($path);
        }
    }
}
