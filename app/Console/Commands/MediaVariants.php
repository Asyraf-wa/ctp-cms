<?php

namespace App\Console\Commands;

use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

// PRD MED-03: queue the WebP variants for images that lack them (uploaded while the cron was not running).
#[Signature('ctp:media-variants {--force : Redo every raster image, not just the incomplete ones}')]
#[Description('Queue WebP variant generation for images that have none or too few')]
class MediaVariants extends Command
{
    public function handle(): int
    {
        $queued = 0;

        Media::query()->orderBy('id')->each(function (Media $media) use (&$queued) {
            if ($media->isRaster() && ($this->option('force') || $this->incomplete($media))) {
                GenerateMediaVariants::dispatch($media->id);
                $queued++;
            }
        });

        $this->components->info("Queued {$queued} image(s); the scheduler works through them.");

        return self::SUCCESS;
    }

    private function incomplete(Media $media): bool
    {
        $variants = $media->variants ?? [];

        if (! isset($variants['og'])) {
            return true;
        }

        // Widths at or above the original are skipped by the generator, so only smaller ones are expected.
        foreach (config('ctp.media.widths') as $width) {
            if ((! $media->width || $media->width > $width) && ! isset($variants[(string) $width])) {
                return true;
            }
        }

        return false;
    }
}
