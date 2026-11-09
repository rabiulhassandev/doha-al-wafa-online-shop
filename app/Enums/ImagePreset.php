<?php

namespace App\Enums;

enum ImagePreset: string
{
    case CategoryImage = 'categories';
    case CollectionBanner = 'collections';
    case ColorSwatch = 'swatches';

    /**
     * Get the storage directory for images of this kind.
     */
    public function directory(): string
    {
        return $this->value;
    }

    /**
     * Get the largest width, in pixels, an image of this kind is stored at.
     */
    public function maxWidth(): int
    {
        return match ($this) {
            self::CategoryImage => 800,
            self::CollectionBanner => 1920,
            self::ColorSwatch => 160,
        };
    }

    /**
     * Determine whether images of this kind are cropped to a square.
     */
    public function isSquare(): bool
    {
        return $this === self::ColorSwatch;
    }
}
