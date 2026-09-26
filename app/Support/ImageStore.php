<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

/**
 * One place for "read an upload, scale it, re-encode it as JPEG, store it on the public disk".
 *
 * That block was written eight times across five controllers, each repeating the directory
 * creation, the generated filename and its own hardcoded width. The filename is always
 * generated here and never taken from the upload, because the contents are re-encoded as
 * JPEG regardless of what arrived.
 *
 * It is also where the card-sized derivative lives. Property photos are stored at 1200px and
 * were rendered straight into 390px cards, so a listing grid downloaded several full-size
 * photos to draw thumbnails — the single biggest thing a visitor waits for on this site.
 */
class ImageStore
{
    /**
     * Width of the card-sized derivative. The widest card this design renders is 392px
     * (--fit-max: 24.5rem on the featured and team grids), so 600 covers every card slot
     * with headroom for a higher-density display without shipping the full-size file.
     */
    public const THUMBNAIL_WIDTH = 600;

    private const THUMBNAIL_DIR = 'thumbs';

    /**
     * Store an upload under $directory, scaled to at most $width, encoded as JPEG.
     * Returns the stored path.
     */
    public static function putScaled(mixed $file, string $directory, int $width, int $quality = 85, ?string $filename = null): string
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory($directory);

        $path = rtrim($directory, '/').'/'.($filename ?: uniqid().'.jpg');

        $disk->put($path, Image::read($file)->scaleDown(width: $width)->toJpeg($quality));

        return $path;
    }

    /** As putScaled, and also writes the card-sized copy beside it. */
    public static function putScaledWithThumbnail(mixed $file, string $directory, int $width, int $quality = 85): string
    {
        $path = self::putScaled($file, $directory, $width, $quality);

        self::writeThumbnail($path);

        return $path;
    }

    /**
     * Write (or rewrite) the derivative for an image already on disk. Returns its path, or
     * null when the original is missing — a row can outlive its file, and a backfill over
     * historical rows should skip those rather than fail.
     */
    public static function writeThumbnail(string $path): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $thumbnail = self::thumbnailFor($path);
        $disk->makeDirectory(dirname($thumbnail));
        $disk->put($thumbnail, Image::read($disk->get($path))->scaleDown(width: self::THUMBNAIL_WIDTH)->toJpeg(80));

        return $thumbnail;
    }

    /** tenants/1/properties/abc.jpg becomes tenants/1/properties/thumbs/abc.jpg */
    public static function thumbnailFor(string $path): string
    {
        $directory = dirname($path);

        return ($directory === '.' ? '' : $directory.'/').self::THUMBNAIL_DIR.'/'.basename($path);
    }

    /**
     * The derivative if one was generated, otherwise the original. Rows predate the
     * derivative, and an upload can fail halfway, so no view should assume one exists.
     */
    public static function thumbnailOrOriginal(?string $path): ?string
    {
        if (! $path) {
            return $path;
        }

        $thumbnail = self::thumbnailFor($path);

        return Storage::disk('public')->exists($thumbnail) ? $thumbnail : $path;
    }

    /** Delete an image and any derivative written beside it. */
    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete([$path, self::thumbnailFor($path)]);
    }
}
