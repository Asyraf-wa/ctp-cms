<?php

namespace App\Seo;

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * SEO-05: sitemap index and child sitemaps, cached until content changes (flush() runs on save/delete).
 * Only indexable URLs are listed: published content, tags with at least two articles, no filtered lists.
 */
class Sitemap
{
    public const SECTIONS = ['pages', 'articles', 'categories', 'tags', 'series', 'projects', 'authors'];

    /** @return array<string, ?string> section => lastmod (only non-empty sections) */
    public function index(): array
    {
        return Cache::rememberForever('sitemap.index', fn () => collect(self::SECTIONS)
            ->mapWithKeys(fn (string $s) => [$s => $this->entries($s)])
            ->filter(fn (Collection $entries) => $entries->isNotEmpty())
            ->map(fn (Collection $entries) => $entries->pluck('lastmod')->filter()->max())
            ->all());
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string, images?: array<int, array{loc: string, title?: ?string}>}> */
    public function entries(string $section): Collection
    {
        // Plain arrays in the cache: Laravel does not unserialize objects from it by default.
        return collect(Cache::rememberForever("sitemap.{$section}", fn () => $this->build($section)->values()->all()));
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string, images?: array<int, array{loc: string, title?: ?string}>}> */
    private function build(string $section): Collection
    {
        return match ($section) {
            'pages' => $this->pages(),
            'articles' => $this->articles(),
            'categories' => Category::query()->whereHas('articles', fn ($q) => $q->published())
                ->orWhereHas('children.articles', fn ($q) => $q->published())
                ->get()->map(fn (Category $c) => ['loc' => url($c->publicPath()), 'lastmod' => $c->updated_at?->toAtomString()]),
            'tags' => Tag::query()->withCount(['articles' => fn ($q) => $q->published()])->get()
                ->filter(fn (Tag $t) => $t->getAttribute('articles_count') >= 2)
                ->map(fn (Tag $t) => ['loc' => url($t->publicPath()), 'lastmod' => $t->updated_at?->toAtomString()])->values(),
            'series' => Series::query()->whereHas('articles', fn ($q) => $q->published())->get()
                ->map(fn (Series $s) => ['loc' => url($s->publicPath()), 'lastmod' => $s->updated_at?->toAtomString()]),
            'projects' => Project::published()->with('cover')->get()->map(fn (Project $p) => [
                'loc' => url($p->publicPath()),
                'lastmod' => $p->updated_at?->toAtomString(),
                'images' => $p->cover ? [['loc' => $p->cover->url(), 'title' => $p->title]] : [],
            ]),
            'authors' => User::query()->whereHas('articles', fn ($q) => $q->published())->get()
                ->map(fn (User $u) => ['loc' => url($u->publicPath() ?? '/'), 'lastmod' => $u->updated_at?->toAtomString()]),
            default => collect(),
        };
    }

    public static function flush(): void
    {
        Cache::forget('sitemap.index');
        foreach (self::SECTIONS as $section) {
            Cache::forget("sitemap.{$section}");
        }
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string}> */
    private function pages(): Collection
    {
        $latest = Article::published()->max('updated_at');
        $sections = collect(['/', '/tutorials', '/blog', '/projects', '/series'])
            ->map(fn (string $path) => ['loc' => url($path), 'lastmod' => $latest ? Carbon::parse($latest)->toAtomString() : null]);

        return $sections->concat(Page::query()->where('is_published', true)->orderBy('sort')->get()
            ->map(fn (Page $p) => ['loc' => url($p->publicPath() ?? '/'), 'lastmod' => $p->updated_at?->toAtomString()]));
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string, images: array<int, array{loc: string, title: string}>}> */
    private function articles(): Collection
    {
        $articles = Article::published()
            ->with(['featuredMedia', 'seo:id,seoable_type,seoable_id,robots,canonical'])
            ->orderByDesc('published_at')
            ->get(['id', 'type', 'title', 'slug', 'body', 'featured_media_id', 'published_at', 'updated_at', 'content_updated_at']);

        // Images used in bodies, loaded once for all articles.
        $bodyMedia = Media::query()->whereIn('id', $articles->flatMap(fn (Article $a) => $a->body ? Article::mediaIdsIn($a->body) : [])->unique())->get()->keyBy('id');

        return $articles
            // Articles marked noindex or canonical to another site do not belong in our sitemap.
            ->reject(fn (Article $a) => str_contains((string) $a->seo?->robots, 'noindex') || filled($a->seo?->canonical))
            ->map(fn (Article $a) => [
                'loc' => url($a->publicPath() ?? '/'),
                'lastmod' => ($a->content_updated_at ?? $a->updated_at ?? $a->published_at)?->toAtomString(),
                'images' => collect([$a->featuredMedia, ...collect($a->body ? Article::mediaIdsIn($a->body) : [])->map(fn ($id) => $bodyMedia->get($id))])
                    ->filter(fn (?Media $m) => $m?->isImage())
                    ->unique('id')
                    ->map(fn (Media $m) => ['loc' => $m->url(), 'title' => $m->alt ?: $a->title])
                    ->values()->all(),
            ])->values();
    }
}
