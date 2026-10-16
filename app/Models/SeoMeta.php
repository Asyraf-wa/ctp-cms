<?php

namespace App\Models;

use App\Models\Concerns\TracksMediaUsage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// PRD SEO-01: per-page overrides; empty fields fall back to the content and the site defaults.
#[Fillable(['title', 'description', 'keywords', 'canonical', 'robots', 'og_media_id'])]
class SeoMeta extends Model
{
    use TracksMediaUsage;

    protected $table = 'seo_meta';

    /** @return MorphTo<Model, $this> */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Media, $this> */
    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }

    public function mediaUsage(): array
    {
        return ['og_image' => [$this->og_media_id]];
    }

    public function mediaUsageLabel(): string
    {
        $owner = $this->seoable;

        return 'Social image: '.($owner && method_exists($owner, 'mediaUsageLabel') ? $owner->mediaUsageLabel() : class_basename($this->seoable_type));
    }
}
