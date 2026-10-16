<?php

namespace App\Jobs;

use App\Media\VariantGenerator;
use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

// PRD MED-03. On shared hosting the queue runs from cron (ARCH §9).
class GenerateMediaVariants implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $mediaId) {}

    public function handle(VariantGenerator $variants): void
    {
        if ($media = Media::find($this->mediaId)) {
            $variants->generate($media);
        }
    }
}
