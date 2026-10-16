<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksMediaUsage;
use App\Models\Contracts\HasPublicPath;
use Database\Factories\SeriesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// PRD TAX-03: an ordered group of tutorials ("Part 2 of 5"). Articles join in Phase 1D.
#[Fillable(['title', 'slug', 'description', 'cover_media_id', 'sort'])]
class Series extends Model implements HasPublicPath
{
    /** @use HasFactory<SeriesFactory> */
    use HasFactory, HasSlug, TracksMediaUsage;

    protected $table = 'series';

    public function pathForSlug(string $slug): ?string
    {
        return '/series/'.$slug;
    }

    /** @return BelongsTo<Media, $this> */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function mediaUsage(): array
    {
        return ['cover' => [$this->cover_media_id]];
    }

    public function mediaUsageLabel(): string
    {
        return 'Series: '.$this->title;
    }
}
