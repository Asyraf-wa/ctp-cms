<?php

namespace App\Seo;

use App\Content\MarkdownExporter;
use App\Enums\ArticleType;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Series;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use Illuminate\Support\Facades\Cache;

/**
 * GEO-01, GEO-02: Markdown for AI assistants and readers.
 * /llms.txt follows https://llmstxt.org (summary + curated links), /llms-full.txt holds every article,
 * and each tutorial, post and page has a .md twin with the facts up front (GEO-04).
 */
class Llms
{
    public function __construct(
        private MarkdownExporter $exporter,
        private GeneralSettings $general,
        private SeoSettings $seo,
    ) {}

    public function article(Article $article): string
    {
        $article->loadMissing(['author', 'category', 'tags', 'technologies', 'series']);
        $tz = config('ctp.display_timezone');
        $facts = array_filter([
            'URL' => url($article->publicPath() ?? '/'),
            'Author' => $article->author ? $article->author->authorName().' ('.url($article->author->publicPath() ?? '/').')' : null,
            'Published' => $article->published_at?->timezone($tz)->toDateString(),
            'Updated' => $article->lastUpdated()?->timezone($tz)->toDateString(),
            'Category' => $article->type === ArticleType::Blog ? 'Blog' : $article->category?->name,
            'Level' => $article->difficulty?->getLabel(),
            // @phpstan-ignore property.notFound (pivot is set by the belongsToMany relation)
            'Tested with' => $article->technologies->map(fn ($t) => trim($t->name.' '.$t->pivot->version))->join(', ') ?: null,
            'Series' => $article->series ? $article->series->title.' (part '.$article->series_position.')' : null,
            'Tags' => $article->tags->pluck('name')->join(', ') ?: null,
            'Reading time' => $article->readingMinutes().' min',
        ]);

        $parts = ['# '.$article->title];
        if ($article->excerpt) {
            $parts[] = '> '.$article->excerpt;
        }
        $parts[] = collect($facts)->map(fn ($value, $label) => "- {$label}: {$value}")->join("\n");
        if ($article->tldr) {
            $parts[] = "## Key takeaways\n\n".collect($article->tldr)->map(fn ($p) => '- '.$p)->join("\n");
        }
        if ($article->prerequisites) {
            $parts[] = "## Before you start\n\n".collect($article->prerequisites)->map(fn ($p) => '- '.$p)->join("\n");
        }
        $parts[] = trim($this->exporter->export($article->body));
        if ($article->faq) {
            $parts[] = "## FAQ\n\n".collect($article->faq)->map(fn ($i) => '### '.$i['question']."\n\n".$i['answer'])->join("\n\n");
        }
        if ($article->repo_url) {
            $parts[] = 'Source code: '.$article->repo_url;
        }

        return implode("\n\n", array_filter($parts))."\n";
    }

    public function page(Page $page): string
    {
        return implode("\n\n", array_filter([
            '# '.$page->title,
            $page->excerpt ? '> '.$page->excerpt : null,
            '- URL: '.url($page->publicPath() ?? '/')."\n- Updated: ".$page->updated_at?->toDateString(),
            trim($this->exporter->export($page->body)),
        ]))."\n";
    }

    public function index(): string
    {
        return Cache::rememberForever('llms.index', function () {
            $lines = ['# '.$this->general->site_name, '', '> '.($this->seo->meta_description ?: $this->general->description), ''];
            $lines[] = 'Step-by-step web development tutorials by '.($this->seo->meta_author ?: $this->general->site_name).'. Every tutorial below has a Markdown version (the .md links); the full text of all articles is in '.url('/llms-full.txt').'.';

            $tutorials = Article::published()->tutorials()->with('category')->latest('published_at')->get(['id', 'type', 'title', 'slug', 'excerpt', 'category_id', 'published_at']);
            foreach (Category::query()->roots()->ordered()->with('children')->get() as $category) {
                $items = $tutorials->whereIn('category_id', $category->familyIds());
                if ($items->isEmpty()) {
                    continue;
                }
                $lines[] = '';
                $lines[] = '## '.$category->name.' tutorials';
                $lines[] = '';
                foreach ($items as $article) {
                    $lines[] = $this->link($article->title, url($article->publicPath()).'.md', $article->excerpt);
                }
            }

            $series = Series::query()->whereHas('articles', fn ($q) => $q->published())->orderBy('sort')->get();
            if ($series->isNotEmpty()) {
                array_push($lines, '', '## Learning paths', '');
                foreach ($series as $item) {
                    $lines[] = $this->link($item->title, url($item->publicPath()), $item->description);
                }
            }

            $posts = Article::published()->blogPosts()->latest('published_at')->limit(20)->get(['id', 'type', 'title', 'slug', 'excerpt']);
            if ($posts->isNotEmpty()) {
                array_push($lines, '', '## Blog', '');
                foreach ($posts as $post) {
                    $lines[] = $this->link($post->title, url($post->publicPath()).'.md', $post->excerpt);
                }
            }

            $pages = Page::query()->where('is_published', true)->orderBy('sort')->get();
            array_push($lines, '', '## Optional', '');
            foreach ($pages as $page) {
                $lines[] = $this->link($page->title, url($page->publicPath() ?? '/').'.md', $page->excerpt);
            }
            $lines[] = $this->link('All tutorials', url('/tutorials'), null);
            $lines[] = $this->link('RSS feed', url('/feed'), null);

            return implode("\n", $lines)."\n";
        });
    }

    public function full(): string
    {
        return Cache::rememberForever('llms.full', function () {
            $articles = Article::published()->with(['author', 'category', 'tags', 'technologies', 'series'])->latest('published_at')->get();

            return '# '.$this->general->site_name.": all articles\n\n> ".($this->seo->meta_description ?: '')."\n\n"
                .$articles->map(fn (Article $a) => $this->article($a))->join("\n---\n\n");
        });
    }

    public static function flush(): void
    {
        Cache::forget('llms.index');
        Cache::forget('llms.full');
    }

    private function link(string $title, string $url, ?string $notes): string
    {
        return '- ['.str_replace(['[', ']'], ['\\[', '\\]'], $title).']('.$url.')'.(filled($notes) ? ': '.trim((string) $notes) : '');
    }
}
