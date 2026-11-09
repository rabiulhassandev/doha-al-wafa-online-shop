<?php

namespace App\Services;

use App\Enums\ImagePreset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ImageStore
{
    private const WEBP_QUALITY = 82;

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
