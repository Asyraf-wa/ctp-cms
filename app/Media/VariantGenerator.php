<?php

namespace App\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

// PRD MED-03: WebP widths for srcset, a 1200×630 social crop, optional AVIF.
class VariantGenerator
{
    public function __construct(private ImageManager $images) {}

    // Just the small preview, done during the upload request so the grid shows it at once.
    public function thumbnail(Media $media): void
    {
        $this->generate($media, ['320'], og: false);
    }

    /** @param list<string>|null $widths */
    public function generate(Media $media, ?array $widths = null, bool $og = true): void
    {
        if (! $media->isRaster()) {
            return;
        }

        $disk = Storage::disk($media->disk);
        $base = preg_replace('/\.[^.\/]+$/', '', $media->path);
        $quality = (int) config('ctp.media.quality');
        $variants = $media->variants ?? [];

        foreach ($widths ?? array_map('strval', config('ctp.media.widths')) as $width) {
            // No upscaling: the original already covers widths at or above its own (the thumb is always made).
            if ($width !== '320' && $media->width && $media->width <= (int) $width) {
                continue;
            }

            $image = $this->read($media)->scaleDown(width: (int) $width);
            $disk->put($variants[$width] = "{$base}-{$width}.webp", (string) $image->toWebp($quality));

            if (config('ctp.media.avif') && function_exists('imageavif')) {
                $disk->put($variants["{$width}-avif"] = "{$base}-{$width}.avif", (string) $image->toAvif($quality));
            }
        }

        if ($og) {
            [$w, $h] = config('ctp.media.og');
            $disk->put($variants['og'] = "{$base}-og.webp", (string) $this->read($media)->cover($w, $h)->toWebp($quality));
        }

        $media->forceFill(['variants' => $variants])->saveQuietly();
    }

    public function deleteAll(Media $media): void
    {
        Storage::disk($media->disk)->delete(array_values($media->variants ?? []));
        $media->forceFill(['variants' => null])->saveQuietly();
    }

    private function read(Media $media): ImageInterface
    {
        return $this->images->read(Storage::disk($media->disk)->get($media->path));
    }
}
