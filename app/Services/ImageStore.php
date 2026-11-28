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
     * Product photo widths, largest first. Heights retain the original aspect ratio.
     */
    public const PRODUCT_WIDTHS = [1200, 800, 400];

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
     * Store a full-resolution WebP source and proportional copies at each product width.
     *
     * Widths larger than the photo are skipped, so small photos are never enlarged.
     * The legacy focus argument is accepted for existing callers but no cropping is performed.
     * Writes "{directory}/source.webp" and "{directory}/{width}.webp".
     *
     * @return list<int> the widths that were stored, largest first
     */
    public function storeProductImage(string $sourcePath, string $directory, ImageFocus $focus = ImageFocus::Center): array
    {
        $disk = Storage::disk(config('shop.media_disk'));
        $encoder = new WebpEncoder(quality: self::WEBP_QUALITY);

        $image = $this->images->decodePath($sourcePath);
        $disk->put("{$directory}/source.webp", $image->encode(new WebpEncoder(quality: 100, strip: true))->toString());
        $originalWidth = $image->width();
        $widths = array_values(array_filter(self::PRODUCT_WIDTHS, fn (int $width): bool => $width <= $originalWidth));
        if ($widths === []) {
            $widths = [$originalWidth];
        }

        $storedWidths = [];

        foreach ($widths as $width) {
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
