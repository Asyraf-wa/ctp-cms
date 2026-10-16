<?php

namespace App\Seo;

use App\Enums\ArticleType;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use App\Settings\SocialSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the Meta for each page type (SEO-01) and the JSON-LD @graph (SEO-04).
 * Fallbacks: per-item SEO override → content → site settings.
 */
class Seo
{
    public function __construct(
        private GeneralSettings $general,
        private SeoSettings $settings,
        private SocialSettings $social,
    ) {}

    // --- Page types -----------------------------------------------------------------------------

    public function basic(?string $title = null, ?string $description = null, ?string $robots = null): Meta
    {
        return new Meta(title: $title, description: $description, robots: $robots, canonical: $this->currentUrl(), image: $this->defaultImage());
    }

    /**
     * @param  list<array{0: string, 1: ?string}>  $breadcrumbs
     * @param  LengthAwarePaginator<int, mixed>|null  $paginator
     * @param  Collection<int, mixed>|null  $items  list items when there is no paginator
     */
    public function listing(string $title, ?string $description, array $breadcrumbs, ?LengthAwarePaginator $paginator = null, ?string $robots = null, ?Collection $items = null): Meta
    {
        $meta = $this->basic($title, $description, $robots);
        $meta->breadcrumbs = $breadcrumbs;
        $this->paginate($meta, $paginator);
        $meta->schema[] = $this->collectionPage($meta, $items ?? ($paginator ? collect($paginator->items()) : collect()));

        return $meta;
    }

    public function home(): Meta
    {
        return new Meta(
            description: $this->settings->meta_description ?: $this->general->description,
            canonical: url('/'),
            image: $this->defaultImage(),
        );
    }

    public function article(Article $article, bool $preview = false): Meta
    {
        $article->loadMissing(['seo.ogImage', 'featuredMedia', 'category.parent', 'tags', 'author', 'technologies', 'series']);
        $url = url($article->publicPath() ?? '/');
        $isBlog = $article->type === ArticleType::Blog;

        $breadcrumbs = $isBlog
            ? [['Blog', url('/blog')], [$article->title, null]]
            : array_values(array_filter([
                ['Tutorials', url('/tutorials')],
                $article->category?->parent ? [$article->category->parent->name, url($article->category->parent->publicPath())] : null,
                $article->category ? [$article->category->name, url($article->category->publicPath())] : null,
                [$article->title, null],
            ]));

        $meta = new Meta(
            title: $article->seo?->title ?: $article->title,
            description: $this->describe($article->seo?->description ?: $article->excerpt, $article->body_text),
            canonical: $article->seo?->canonical ?: $url,
            robots: $preview ? 'noindex, nofollow' : $article->seo?->robots,
            type: 'article',
            publishedAt: $article->published_at,
            modifiedAt: $article->lastUpdated() ?? $article->published_at,
            author: $article->author?->authorName(),
            section: $isBlog ? 'Blog' : $article->category?->name,
            tags: $article->tags->pluck('name')->all(),
            breadcrumbs: $breadcrumbs,
            markdownUrl: $preview ? null : $url.'.md',
        );
        $this->image($meta, $article->seo?->ogImage, OgImage::url('article', $article), $article->title);

        $meta->schema = array_values(array_filter([
            $this->articleNode($article, $meta, $url),
            $article->author ? $this->person($article->author) : null,
            $this->faq($article, $url),
            $this->howTo($article, $url),
        ]));

        return $meta;
    }

    public function page(Page $page, bool $preview = false): Meta
    {
        $page->loadMissing('seo.ogImage');
        $url = url($page->publicPath() ?? '/');
        $meta = new Meta(
            title: $page->seo?->title ?: $page->title,
            description: $this->describe($page->seo?->description ?: $page->excerpt, strip_tags((string) $page->body_rendered)),
            canonical: $page->seo?->canonical ?: $url,
            robots: $preview ? 'noindex, nofollow' : $page->seo?->robots,
            breadcrumbs: [[$page->title, null]],
            markdownUrl: $preview ? null : $url.'.md',
        );
        $this->image($meta, $page->seo?->ogImage, OgImage::url('page', $page), $page->title);
        $meta->schema[] = [
            '@type' => $page->template === 'about' ? 'AboutPage' : 'WebPage',
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $meta->title,
            'description' => $meta->description,
            'isPartOf' => ['@id' => $this->websiteId()],
            'dateModified' => $page->updated_at?->toAtomString(),
            'breadcrumb' => ['@id' => $url.'#breadcrumb'],
        ];

        return $meta;
    }

    /** @param  Collection<int, Article>  $articles */
    public function project(Project $project, Collection $articles): Meta
    {
        $project->loadMissing(['seo.ogImage', 'cover', 'technologies']);
        $url = url($project->publicPath() ?? '/');
        $meta = new Meta(
            title: $project->seo?->title ?: $project->title,
            description: $this->describe($project->seo?->description ?: $project->tagline, strip_tags((string) $project->body_rendered)),
            canonical: $project->seo?->canonical ?: $url,
            robots: $project->seo?->robots,
            breadcrumbs: [['Projects', url('/projects')], [$project->title, null]],
        );
        $this->image($meta, $project->seo?->ogImage, OgImage::url('project', $project), $project->title);

        $meta->schema[] = array_filter([
            '@type' => 'SoftwareSourceCode',
            '@id' => $url.'#project',
            'name' => $project->title,
            'description' => $meta->description,
            'url' => $url,
            'image' => $meta->image,
            'codeRepository' => $project->repo_url,
            'programmingLanguage' => $project->technologies->pluck('name')->all() ?: null,
            'dateCreated' => $project->started_at?->toDateString(),
            'creativeWorkStatus' => $project->status->getLabel(),
            'author' => ['@id' => $this->organizationId()],
            'sameAs' => $project->live_url ? [$project->live_url] : null,
            'subjectOf' => $articles->map(fn (Article $a) => ['@type' => 'TechArticle', 'headline' => $a->title, 'url' => url($a->publicPath())])->all() ?: null,
        ]);

        return $meta;
    }

    /** @param  LengthAwarePaginator<int, Article>  $articles */
    public function category(Category $category, LengthAwarePaginator $articles): Meta
    {
        $crumbs = array_values(array_filter([
            ['Tutorials', url('/tutorials')],
            $category->parent ? [$category->parent->name, url($category->parent->publicPath())] : null,
            [$category->name, null],
        ]));
        $meta = $this->listing($category->seo_title ?: $category->name.' tutorials',
            $category->seo_description ?: ($category->description ?: 'Step-by-step '.$category->name.' tutorials.'),
            $crumbs, $articles);
        $meta->image = OgImage::url('category', $category);

        return $meta;
    }

    /** @param  LengthAwarePaginator<int, Article>  $articles */
    public function tag(Tag $tag, LengthAwarePaginator $articles): Meta
    {
        // Thin tag pages (one article) stay out of the index.
        return $this->listing('#'.$tag->name, $tag->description ?: 'Tutorials and posts tagged '.$tag->name.'.',
            [['Tutorials', url('/tutorials')], ['#'.$tag->name, null]], $articles,
            $articles->total() < 2 ? 'noindex, follow' : null);
    }

    /** @param  Collection<int, Article>  $articles */
    public function series(Series $series, Collection $articles): Meta
    {
        $meta = $this->listing($series->title, $series->description ?: 'A step-by-step tutorial series.',
            [['Series', url('/series')], [$series->title, null]], null, null, $articles);
        $this->image($meta, null, OgImage::url('series', $series), $series->title);
        $meta->schema[] = [
            '@type' => 'CreativeWorkSeries',
            '@id' => url($series->publicPath()).'#series',
            'name' => $series->title,
            'description' => $meta->description,
            'url' => url($series->publicPath()),
            'hasPart' => $articles->map(fn (Article $a) => ['@type' => 'TechArticle', 'headline' => $a->title, 'url' => url($a->publicPath()), 'position' => $a->series_position])->all(),
        ];

        return $meta;
    }

    /** @param  LengthAwarePaginator<int, Article>  $articles */
    public function author(User $author, LengthAwarePaginator $articles): Meta
    {
        $url = url($author->publicPath() ?? '/');
        $meta = new Meta(
            title: $author->authorName(),
            description: $this->describe($author->bio_short, strip_tags((string) $author->bio_long)) ?: 'Tutorials and posts by '.$author->authorName().'.',
            canonical: $url,
            type: 'profile',
            image: $author->avatarUrl() ?: $this->defaultImage(),
            breadcrumbs: [[$author->authorName(), null]],
        );
        $this->paginate($meta, $articles);
        $meta->schema[] = ['@type' => 'ProfilePage', '@id' => $url.'#webpage', 'url' => $url, 'name' => $author->authorName(), 'mainEntity' => ['@id' => $this->personId($author)], 'isPartOf' => ['@id' => $this->websiteId()]];
        $meta->schema[] = $this->person($author);

        return $meta;
    }

    // --- JSON-LD --------------------------------------------------------------------------------

    /** @return array<string, mixed> the full JSON-LD document for the page */
    public function graph(Meta $meta): array
    {
        $graph = [$this->website(), $this->organization()];
        if ($meta->breadcrumbs) {
            $graph[] = $this->breadcrumbList($meta);
        }

        return ['@context' => 'https://schema.org', '@graph' => [...$graph, ...$meta->schema]];
    }

    /** @return array<string, mixed> */
    private function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $this->websiteId(),
            'url' => url('/'),
            'name' => $this->general->site_name,
            'alternateName' => $this->general->site_abbr,
            'description' => $this->settings->meta_description,
            'inLanguage' => 'en',
            'publisher' => ['@id' => $this->organizationId()],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/search').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function organization(): array
    {
        return array_filter([
            '@type' => 'Organization',
            '@id' => $this->organizationId(),
            'name' => $this->general->site_name,
            'legalName' => $this->general->organization_name,
            'url' => url('/'),
            'logo' => ['@type' => 'ImageObject', 'url' => asset('apple-touch-icon.png'), 'width' => 180, 'height' => 180],
            'email' => $this->general->contact_email,
            'sameAs' => array_values($this->social->links()) ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    private function person(User $user): array
    {
        $sameAs = collect($user->socials ?? [])->filter()->values()->push($user->website_url)->filter()->unique()->values()->all();

        return array_filter([
            '@type' => 'Person',
            '@id' => $this->personId($user),
            'name' => $user->authorName(),
            'url' => url($user->publicPath() ?? '/'),
            'image' => $user->avatarUrl(),
            'jobTitle' => $user->job_title,
            'description' => $user->bio_short,
            'sameAs' => $sameAs ?: null,
            'worksFor' => ['@id' => $this->organizationId()],
        ]);
    }

    /** @return array<string, mixed> */
    private function breadcrumbList(Meta $meta): array
    {
        $items = [['Home', url('/')], ...$meta->breadcrumbs];

        return [
            '@type' => 'BreadcrumbList',
            '@id' => ($meta->canonical ?? $this->currentUrl()).'#breadcrumb',
            'itemListElement' => collect($items)->values()->map(fn (array $crumb, int $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                // The last crumb is the page itself.
                'item' => $crumb[1] ?? ($i === count($items) - 1 ? $meta->canonical : null),
            ]))->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function articleNode(Article $article, Meta $meta, string $url): array
    {
        $isBlog = $article->type === ArticleType::Blog;
        // @phpstan-ignore property.notFound (pivot is set by the belongsToMany relation)
        $stack = $article->technologies->map(fn ($t) => trim($t->name.' '.$t->pivot->version));

        return array_filter([
            '@type' => $isBlog ? 'BlogPosting' : 'TechArticle',
            '@id' => $url.'#article',
            'headline' => Str::limit($article->title, 110, ''),
            'description' => $meta->description,
            'url' => $url,
            'mainEntityOfPage' => $url,
            'image' => $meta->image ? [$meta->image] : null,
            'datePublished' => $article->published_at?->toAtomString(),
            'dateModified' => ($article->lastUpdated() ?? $article->published_at)?->toAtomString(),
            'author' => $article->author ? ['@id' => $this->personId($article->author)] : null,
            'publisher' => ['@id' => $this->organizationId()],
            'isPartOf' => $article->series
                ? ['@type' => 'CreativeWorkSeries', 'name' => $article->series->title, 'url' => url($article->series->publicPath())]
                : ['@id' => $this->websiteId()],
            'inLanguage' => 'en',
            'articleSection' => $meta->section,
            'keywords' => $meta->tags ? implode(', ', $meta->tags) : null,
            'about' => $article->technologies->map(fn ($t) => ['@type' => 'Thing', 'name' => $t->name])->all() ?: null,
            'wordCount' => $article->word_count ?: null,
            'timeRequired' => 'PT'.$article->readingMinutes().'M',
            'proficiencyLevel' => $isBlog ? null : $article->difficulty?->getLabel(),
            // GEO-04: the versions the tutorial was tested with.
            'dependencies' => ! $isBlog && $stack->isNotEmpty() ? $stack->join(', ') : null,
            'breadcrumb' => ['@id' => $url.'#breadcrumb'],
        ]);
    }

    /** @return array<string, mixed>|null GEO-06 */
    private function faq(Article $article, string $url): ?array
    {
        $faq = collect($article->faq ?? [])->filter(fn (array $i) => filled($i['question']) && filled($i['answer']));

        return $faq->isEmpty() ? null : [
            '@type' => 'FAQPage',
            '@id' => $url.'#faq',
            'mainEntity' => $faq->map(fn ($i) => [
                '@type' => 'Question',
                'name' => $i['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['answer']],
            ])->values()->all(),
        ];
    }

    /** @return array<string, mixed>|null HowTo from the first Steps block */
    private function howTo(Article $article, string $url): ?array
    {
        $steps = $this->findSteps($article->body ?? []);

        return $steps ? [
            '@type' => 'HowTo',
            '@id' => $url.'#howto',
            'name' => $article->title,
            'description' => Str::limit((string) $article->excerpt, 300),
            'totalTime' => 'PT'.$article->readingMinutes().'M',
            'step' => collect($steps)->values()->map(fn (array $step, int $i) => array_filter([
                '@type' => 'HowToStep',
                'position' => $i + 1,
                'name' => $step['title'] ?? 'Step '.($i + 1),
                'text' => trim(strip_tags((string) ($step['body'] ?? ''))) ?: ($step['title'] ?? null),
            ]))->all(),
        ] : null;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function findSteps(array $node): array
    {
        if (($node['type'] ?? null) === 'customBlock' && ($node['attrs']['id'] ?? null) === 'steps') {
            return array_values($node['attrs']['config']['steps'] ?? []);
        }
        foreach ($node['content'] ?? [] as $child) {
            if ($found = $this->findSteps($child)) {
                return $found;
            }
        }

        return [];
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return array<string, mixed>
     */
    private function collectionPage(Meta $meta, Collection $items): array
    {
        $url = $meta->canonical ?? $this->currentUrl();

        return [
            '@type' => 'CollectionPage',
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $meta->title,
            'description' => $meta->description,
            'isPartOf' => ['@id' => $this->websiteId()],
            'breadcrumb' => ['@id' => $url.'#breadcrumb'],
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $items->values()->map(fn ($item, int $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => url($item->publicPath() ?? '/'),
                    'name' => $item->title ?? $item->name,
                ])->all(),
            ],
        ];
    }

    // --- Helpers --------------------------------------------------------------------------------

    /**
     * The social image: the item's "Social sharing image" override, else the generated brand card (PNG, with the title).
     * Featured images are not used by default: their og variant is WebP, which LinkedIn and some apps ignore.
     */
    private function image(Meta $meta, ?Media $media, string $generated, string $alt): void
    {
        if ($media && $media->isImage()) {
            $hasOg = isset($media->variants['og']);
            $meta->image = $media->url($hasOg ? 'og' : null);
            $meta->imageWidth = $hasOg ? 1200 : $media->width;
            $meta->imageHeight = $hasOg ? 630 : $media->height;
            $meta->imageAlt = $media->alt ?: $alt;
        } else {
            [$meta->image, $meta->imageWidth, $meta->imageHeight, $meta->imageAlt] = [$generated, 1200, 630, $alt];
        }
    }

    private function defaultImage(): string
    {
        return $this->general->default_og_image
            ? asset('storage/'.$this->general->default_og_image)
            : OgImage::url('site', null);
    }

    /** @param  LengthAwarePaginator<int, mixed>|null  $paginator */
    private function paginate(Meta $meta, ?LengthAwarePaginator $paginator): void
    {
        if (! $paginator) {
            return;
        }
        $page = $paginator->currentPage();
        // Each page of a list is its own canonical (Google no longer uses rel=prev/next, but it costs nothing).
        $meta->canonical = $page > 1 ? $paginator->url($page) : url()->current();
        $meta->prevUrl = $page > 1 ? $paginator->previousPageUrl() : null;
        $meta->nextUrl = $paginator->nextPageUrl();
    }

    private function describe(?string $preferred, ?string $fallbackText): ?string
    {
        $text = filled($preferred) ? $preferred : $fallbackText;

        return filled($text) ? Str::limit(Str::squish((string) $text), 160) : $this->settings->meta_description;
    }

    private function currentUrl(): string
    {
        return url()->current();
    }

    private function websiteId(): string
    {
        return url('/').'/#website';
    }

    private function organizationId(): string
    {
        return url('/').'/#organization';
    }

    private function personId(User $user): string
    {
        return url($user->publicPath() ?? '/').'#person';
    }
}
