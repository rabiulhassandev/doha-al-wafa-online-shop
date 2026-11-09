<?php

namespace App\Services;

use App\Enums\ImageFocus;
use App\Enums\ImagePreset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ImageStore
{
    private const WEBP_QUALITY = 82;

    /**
     * Product photo widths, largest first. Heights follow the 4:5 shape.
     */
    public const PRODUCT_WIDTHS = [1200, 800, 400];

    private const PRODUCT_SOURCE_MAX = 2000;

    public function __construct(private readonly ImageManager $images) {}

    /**
     * Re-encode an uploaded image as WebP at the preset's size and store it.
     *
     * Re-encoding also drops metadata, such as a phone's GPS location, and anything that is not image data.
     */
    public function store(UploadedFile $file, ImagePreset $preset): string
    {
        $image = $this->images->decodePath($file->getRealPath());

        $image = $preset->isSquare()
            ? $image->cover($preset->maxWidth(), $preset->maxWidth())
            : $image->scaleDown(width: $preset->maxWidth());

        $path = $preset->directory().'/'.Str::random(40).'.webp';

        Storage::disk(config('shop.media_disk'))->put(
            $path,
            $image->encode(new WebpEncoder(quality: self::WEBP_QUALITY))->toString(),
        );

        return $path;
    }

    /**
     * Store a product photo: an uncropped source copy plus 4:5 crops at each product width.
     *
     * Widths larger than the cropped photo are skipped, so small photos are never enlarged.
     * Writes "{directory}/source.webp" and "{directory}/{width}.webp".
     *
     * @return list<int> the widths that were stored, largest first
     */
    public function storeProductImage(string $sourcePath, string $directory, ImageFocus $focus): array
    {
        $disk = Storage::disk(config('shop.media_disk'));
        $encoder = new WebpEncoder(quality: self::WEBP_QUALITY);

        $image = $this->images->decodePath($sourcePath)->scaleDown(self::PRODUCT_SOURCE_MAX, self::PRODUCT_SOURCE_MAX);
        $disk->put("{$directory}/source.webp", $image->encode($encoder)->toString());

        $cropWidth = min($image->width(), intdiv($image->height() * 4, 5));
        $image->cover($cropWidth, intdiv($cropWidth * 5, 4), $focus->alignment());

        $storedWidths = [];

        foreach (self::PRODUCT_WIDTHS as $width) {
            if ($width > $cropWidth && $width !== min(self::PRODUCT_WIDTHS)) {
                continue;
            }

            $image->scaleDown(width: $width);
            $disk->put("{$directory}/{$width}.webp", $image->encode($encoder)->toString());
            $storedWidths[] = $width;
        }

        return $storedWidths;
    }

    /**
     * Work out an image attribute's new value from a submitted form.
     *
     * A new upload wins over the remove checkbox. Models delete replaced files themselves.
     */
    public function resolve(?string $currentPath, ?UploadedFile $upload, bool $remove, ImagePreset $preset): ?string
    {
        return match (true) {
            $upload !== null => $this->store($upload, $preset),
            $remove => null,
            default => $currentPath,
        };
    }
}
