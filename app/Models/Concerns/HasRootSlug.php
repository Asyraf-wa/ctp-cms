<?php

namespace App\Models\Concerns;

use App\Models\RootSlug;
use App\Support\Slugs\RootSlugs;

/**
 * D9: the model lives at domain/{slug}. Its slug must be free across every root model
 * (tutorials and pages) and must not be a reserved route segment.
 */
trait HasRootSlug
{
    use HasSlug {
        HasSlug::slugIsTaken as slugIsTakenInTable;
    }

    public static function bootHasRootSlug(): void
    {
        static::saved(function (self $model) {
            RootSlug::where('sluggable_type', $model->getMorphClass())->where('sluggable_id', $model->getKey())->delete();
            RootSlug::create(['slug' => $model->slug, 'sluggable_type' => $model->getMorphClass(), 'sluggable_id' => $model->getKey()]);
        });

        static::deleted(fn (self $model) => RootSlug::where('sluggable_type', $model->getMorphClass())->where('sluggable_id', $model->getKey())->delete());
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/'.$slug;
    }

    protected function slugIsTaken(string $slug): bool
    {
        return RootSlugs::isReserved($slug)
            || $this->slugIsTakenInTable($slug)
            || ! RootSlugs::isAvailable($slug, $this);
    }
}
