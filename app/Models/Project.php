<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\HasRenderedBody;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksMediaUsage;
use App\Models\Contracts\HasPublicPath;
use Database\Factories\ProjectFactory;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * PRD PUB-09: a portfolio project at /projects/{slug}, with a problem → solution write-up.
 *
 * @property ProjectStatus $status
 * @property array<string, mixed>|null $body
 * @property list<array{id: string, label: string, level: int}>|null $toc
 * @property list<array{media_id: int|string|null, caption?: string|null}>|null $gallery
 * @property list<string>|null $stack
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 */
#[Fillable([
    'title', 'slug', 'tagline', 'body', 'cover_media_id', 'gallery', 'stack', 'status',
    'live_url', 'repo_url', 'started_at', 'ended_at', 'is_featured', 'is_published', 'sort',
])]
class Project extends Model implements HasPublicPath, HasRichContent
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasRenderedBody, HasSlug, TracksMediaUsage;

    protected static function booted(): void
    {
        static::saving(function (Project $project) {
            if ($project->isDirty('body') || ! $project->exists) {
                $project->renderBody();
            }
            // Keep the stack tidy for "filter by tech": trimmed, unique, in the order given.
            $project->stack = array_values(array_unique(array_filter(array_map('trim', $project->stack ?? []))));
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'body' => 'array',
            'toc' => 'array',
            'gallery' => 'array',
            'stack' => 'array',
            'started_at' => 'date',
            'ended_at' => 'date',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/projects/'.$slug;
    }

    public function renderBody(): void
    {
        $result = $this->renderRichBody();
        $this->body_rendered = $result['html'];
        $this->toc = $result['toc'];
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_published;
    }

    /** @param Builder<Project> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @return BelongsTo<Media, $this> */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    /** @return BelongsToMany<Article, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'project_article')->withPivot('sort')->orderByPivot('sort');
    }

    /** @return MorphOne<SeoMeta, $this> */
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /** @return list<int> */
    public function galleryMediaIds(): array
    {
        return array_values(array_unique(array_map('intval', array_filter(array_column($this->gallery ?? [], 'media_id')))));
    }

    // "2024 – present", "2023 – 2024" or "2024" for the project timeline.
    public function period(): ?string
    {
        if (! $this->started_at) {
            return null;
        }

        $end = $this->ended_at ? $this->ended_at->format('Y') : ($this->status === ProjectStatus::Archived ? null : 'present');

        return $end && $end !== $this->started_at->format('Y')
            ? $this->started_at->format('Y').' – '.$end
            : $this->started_at->format('Y');
    }

    /** @return list<string> every tech used by a published project, for the filter chips */
    public static function stackOptions(): array
    {
        return static::published()->pluck('stack')->flatten()->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }

    public function mediaUsage(): array
    {
        return [
            'cover' => [$this->cover_media_id],
            'gallery' => $this->galleryMediaIds(),
            'body' => $this->bodyMediaIds(),
        ];
    }

    public function mediaUsageLabel(): string
    {
        return 'Project: '.$this->title;
    }
}
