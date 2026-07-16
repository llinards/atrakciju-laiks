<?php

namespace App\Actions;

use Illuminate\Support\Facades\Image;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

/**
 * Normalize an admin upload to a compact WebP before storing it on the
 * public disk. With a height the image is cropped to cover the exact
 * dimensions; without one it is scaled down proportionally.
 */
class StoreOptimizedImage
{
    /**
     * @param  positive-int  $width
     * @param  positive-int|null  $height
     */
    public function handle(TemporaryUploadedFile $image, string $directory, int $width, ?int $height = null): string
    {
        $pipeline = Image::fromUpload($image)->orient();

        $pipeline = $height !== null
            ? $pipeline->cover($width, $height)
            : $pipeline->scale(width: $width);

        $path = $pipeline->optimize()->storePublicly($directory, 'public');

        if ($path === false) {
            throw new RuntimeException('The optimized image could not be stored.');
        }

        return $path;
    }
}
