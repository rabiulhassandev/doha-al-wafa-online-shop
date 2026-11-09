<?php

namespace App\Enums;

use Intervention\Image\Alignment;

/**
 * Which part of a photo to keep when it is cropped to the 4:5 product shape.
 */
enum ImageFocus: string
{
    case Top = 'top';
    case Center = 'center';
    case Bottom = 'bottom';

    /**
     * Get the label shown in the admin panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Top => 'Top',
            self::Center => 'Center',
            self::Bottom => 'Bottom',
        };
    }

    /**
     * Get the matching crop alignment for the image library.
     */
    public function alignment(): Alignment
    {
        return Alignment::from($this->value);
    }
}
