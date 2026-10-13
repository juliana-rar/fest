<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Resizes uploaded images and stores them as WebP on the public "uploads" disk,
 * together with a lightweight thumbnail (same name under /thumbs).
 */
class ImageStore
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * @param  UploadedFile|string  $source  Uploaded file or raw image binary.
     * @return string Relative path on the uploads disk.
     */
    public function store(UploadedFile|string $source, string $folder = 'fiestas', int $maxWidth = 1600, ?int $thumbWidth = 640): string
    {
        $input = $source instanceof UploadedFile ? $source->getRealPath() : $source;
        $path = trim($folder, '/').'/'.Str::lower(Str::random(16)).'.webp';
        $disk = Storage::disk('uploads');

        $image = $this->manager->read($input);
        $image->scaleDown(width: $maxWidth);
        $disk->put($path, (string) $image->toWebp(quality: 82));

        if ($thumbWidth) {
            $thumb = $this->manager->read($input);
            $thumb->scaleDown(width: $thumbWidth);
            $disk->put(dirname($path).'/thumbs/'.basename($path), (string) $thumb->toWebp(quality: 72));
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('uploads')->delete([$path, dirname($path).'/thumbs/'.basename($path)]);
        }
    }
}
