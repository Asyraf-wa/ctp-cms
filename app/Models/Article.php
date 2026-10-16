<?php

namespace App\Models;

use App\Content\ArticleRenderer;
use App\Content\Blocks;
use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\Difficulty;
use App\Media\MediaLibraryAttachmentProvider;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksMediaUsage;
use App\Models\Contracts\HasPublicPath;
use App\Support\Slugs\RootSlugs;
use Carbon\CarbonInterface;
use Database\Factories\ArticleFactory;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * PRD ART: tutorials live at domain/{slug} (D9), blog posts at /blog/{slug}.
 *
 * @property ArticleType $type
 * @property ArticleStatus $status
 * @property Difficulty|null $difficulty
 * @property array<string, mixed>|null $body
 * @property list<string>|null $tldr
 * @property list<array{id: string, label: string, level: int}>|null $toc
 * @property list<string>|null $prerequisites
 * @property list<array{name: string, version: string}>|null $tested_with
 * @property list<array{question: string, answer: string}>|null $faq
 * @property Carbon|null $published_at
 * @property Carbon|null $content_updated_at
 */
#[Fillable([
    'type', 'author_id', 'title', 'slug', 'excerpt', 'tldr', 'body',
    'featured_media_id', 'category_id', 'series_id', 'series_position',
    'status', 'published_at', 'content_updated_at',
    'difficulty', 'reading_minutes_override', 'prerequisites', 'tested_with', 'repo_url', 'demo_url', 'faq',
    'is_featured', 'is_pinned', 'needs_review', 'comments_enabled', 'views', 'kudos', 'legacy_id',
])]
class Article extends Model implements HasPublicPath, HasRichContent
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasSlug, InteractsWithRichContent, SoftDeletes, TracksMediaUsage;

    public const BLOCKS = [
        Blocks\CodeBlock::class,
        Blocks\ImageBlock::class,
        Blocks\CalloutBlock::class,
        Blocks\CodeTabsBlock::class,
        Blocks\TerminalBlock::class,
        Blocks\StepsBlock::class,
        Blocks\YouTubeBlock::class,
        Blocks\CodePenBlock::class,
    ];

    /** @var list<int> media ids found in the body by the last render */
    protected array $bodyMediaIds = [];

    protected static function booted(): void
    {
        static::saving(function (Article $article) {
            $article->normaliseStatus();
            if ($article->isDirty('body') || ! $article->exists) {
                $article->renderBody();
            }
        });

        static::saved(fn (Article $article) => $article->syncRootSlug());
        static::deleted(fn (Article $article) => RootSlug::where('sluggable_type', 'article')->where('sluggable_id', $article->id)->delete());
        static::restored(fn (Article $article) => $article->syncRootSlug());

        // Moving between tutorial and blog changes the URL: keep the old one working.
        static::updated(function (Article $article) {
            if ($article->wasChanged('type') && ! $article->wasChanged('slug') && $article->isPubliclyVisible()) {
                $oldType = ArticleType::from($article->getOriginal('type') instanceof ArticleType ? $article->getOriginal('type')->value : $article->getOriginal('type'));
                Redirect::record(self::pathFor($oldType, $article->slug), $article->publicPath(), Redirect::SOURCE_SLUG);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => ArticleType::class,
            'status' => ArticleStatus::class,
            'difficulty' => Difficulty::class,
            'tldr' => 'array',
            'body' => 'array',
            'toc' => 'array',
            'prerequisites' => 'array',
            'tested_with' => 'array',
            'faq' => 'array',
            'published_at' => 'datetime',
            'content_updated_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_pinned' => 'boolean',
            'needs_review' => 'boolean',
            'comments_enabled' => 'boolean',
        ];
    }

    public function setUpRichContent(): void
    {
        $this->registerRichContent('body')
            ->json()
            ->customBlocks(self::BLOCKS)
            ->fileAttachmentProvider(new MediaLibraryAttachmentProvider);
    }

    // --- Relations ---------------------------------------------------------------------------

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Series, $this> */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return BelongsTo<Media, $this> */
    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    /** @return HasMany<ArticleRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->latest('id');
    }

    /** @return MorphOne<SeoMeta, $this> */
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    // --- Scopes -------------------------------------------------------------------------------

    /** @param Builder<Article> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published)->where('published_at', '<=', now());
    }

    /** @param Builder<Article> $query */
    public function scopeTutorials(Builder $query): void
    {
        $query->where('type', ArticleType::Tutorial);
    }

    /** @param Builder<Article> $query */
    public function scopeBlogPosts(Builder $query): void
    {
        $query->where('type', ArticleType::Blog);
    }

    // --- URLs and slugs (D9) ------------------------------------------------------------------

    public static function pathFor(ArticleType $type, string $slug): string
    {
        return $type === ArticleType::Blog ? '/blog/'.$slug : '/'.$slug;
    }

    public function pathForSlug(string $slug): ?string
    {
        return self::pathFor($this->type ?? ArticleType::Tutorial, $slug);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === ArticleStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now())
            && ! $this->trashed();
    }

    protected function slugIsTaken(string $slug): bool
    {
        $sameType = static::withTrashed()
            ->where('type', $this->type ?? ArticleType::Tutorial)
            ->where('slug', $slug)
            ->when($this->exists, fn (Builder $q) => $q->whereKeyNot($this->getKey()))
            ->exists();

        if ($sameType) {
            return true;
        }

        // Tutorials share domain/{slug} with pages.
        return ($this->type ?? ArticleType::Tutorial) === ArticleType::Tutorial
            && (RootSlugs::isReserved($slug) || ! RootSlugs::isAvailable($slug, $this));
    }

    private function syncRootSlug(): void
    {
        RootSlug::where('sluggable_type', 'article')->where('sluggable_id', $this->id)->delete();

        if ($this->type === ArticleType::Tutorial && ! $this->trashed()) {
            RootSlug::create(['slug' => $this->slug, 'sluggable_type' => 'article', 'sluggable_id' => $this->id]);
        }
    }

    // --- Publishing (ART-02) --------------------------------------------------------------------

    private function normaliseStatus(): void
    {
        if ($this->status === ArticleStatus::Draft) {
            return;
        }

        $this->published_at ??= now();
        $this->status = $this->published_at->isFuture() ? ArticleStatus::Scheduled : ArticleStatus::Published;
    }

    // --- Content ------------------------------------------------------------------------------

    public function renderBody(): void
    {
        $html = $this->body ? $this->getRichContentAttribute('body')->getRenderer()->toUnsafeHtml() : '';
        $result = app(ArticleRenderer::class)->render($html);

        $this->body_rendered = $result['html'];
        $this->body_text = $result['text'];
        $this->toc = $result['toc'];
        $this->word_count = $result['words'];
        $this->reading_minutes = $result['minutes'];
        $this->bodyMediaIds = $result['media'];
    }

    public function readingMinutes(): int
    {
        return $this->reading_minutes_override ?: $this->reading_minutes;
    }

    // ART-08: flagged by hand, or not updated for a long time.
    public function isOutdated(): bool
    {
        $lastTouched = $this->content_updated_at ?? $this->published_at;

        return $this->needs_review
            || ($lastTouched !== null && $lastTouched->lt(now()->subMonths((int) config('ctp.freshness_months'))));
    }

    public function lastUpdated(): ?CarbonInterface
    {
        return $this->content_updated_at && $this->published_at && $this->content_updated_at->gt($this->published_at)
            ? $this->content_updated_at
            : null;
    }

    // --- Media usage (MED-05) -------------------------------------------------------------------

    public function mediaUsage(): array
    {
        if (! $this->bodyMediaIds && $this->body) {
            $this->bodyMediaIds = self::mediaIdsIn($this->body);
        }

        return [
            'featured' => [$this->featured_media_id],
            'body' => $this->bodyMediaIds,
        ];
    }

    public function mediaUsageLabel(): string
    {
        return ($this->type === ArticleType::Blog ? 'Blog: ' : 'Tutorial: ').$this->title;
    }

    /**
     * Media ids referenced in a TipTap document: editor attachments and Image blocks.
     *
     * @param  array<string, mixed>  $node
     * @return list<int>
     */
    public static function mediaIdsIn(array $node): array
    {
        $ids = [];
        if (($node['type'] ?? null) === 'image' && is_numeric($node['attrs']['id'] ?? null)) {
            $ids[] = (int) $node['attrs']['id'];
        }
        if (($node['type'] ?? null) === 'customBlock' && ($node['attrs']['id'] ?? null) === 'image' && is_numeric($node['attrs']['config']['media_id'] ?? null)) {
            $ids[] = (int) $node['attrs']['config']['media_id'];
        }
        foreach ($node['content'] ?? [] as $child) {
            $ids = [...$ids, ...self::mediaIdsIn($child)];
        }

        return array_values(array_unique($ids));
    }
}
