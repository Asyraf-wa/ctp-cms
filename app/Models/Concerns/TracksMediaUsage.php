<?php

namespace App\Models\Concerns;

use App\Models\MediaUsage;

/**
 * PRD MED-05: record which media a model uses, on every save.
 */
trait TracksMediaUsage
{
    public static function bootTracksMediaUsage(): void
    {
        static::saved(fn (self $model) => MediaUsage::sync($model, $model->mediaUsage()));
        static::deleted(fn (self $model) => MediaUsage::forget($model));
    }

    /** @return array<string, list<int|string|null>> field => media ids */
    abstract public function mediaUsage(): array;

    // A short label for "Used in" lists, e.g. "Series: CakePHP 5 from Zero".
    abstract public function mediaUsageLabel(): string;
}
