<?php

namespace App\Http\Controllers\Seo;

use App\Enums\ArticleType;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use XMLWriter;

/**
 * SEO-07: RSS 2.0 for everything, the blog and each category, plus Atom for everything.
 * Items carry the full rendered article (content:encoded), so feed readers show it all.
 */
class FeedController extends Controller
{
    public const ITEMS = 20;

    public function __construct(private GeneralSettings $general, private SeoSettings $seo) {}

    public function all(): Response
    {
        return $this->rss($this->general->site_name, url('/'), $this->seo->meta_description, route('feed'), $this->latest(Article::published()));
    }

    public function blog(): Response
    {
        return $this->rss($this->general->site_name.' · Blog', url('/blog'), 'Notes, opinions and updates.', route('feed.blog'),
            $this->latest(Article::published()->blogPosts()));
    }

    public function category(string $slug): Response
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        return $this->rss($this->general->site_name.' · '.$category->name, url($category->publicPath()),
            $category->description ?: $category->name.' tutorials.', route('feed.category', $slug),
            $this->latest(Article::published()->tutorials()->whereIn('category_id', $category->familyIds())));
    }

    public function atom(): Response
    {
        $items = $this->latest(Article::published());
        $xml = $this->document();
        $xml->startElement('feed');
        $xml->writeAttribute('xmlns', 'http://www.w3.org/2005/Atom');
        $xml->writeElement('title', $this->general->site_name);
        $xml->writeElement('subtitle', (string) $this->seo->meta_description);
        $xml->writeElement('id', url('/').'/');
        $this->link($xml, url('/'), 'alternate');
        $this->link($xml, route('feed.atom'), 'self');
        $xml->writeElement('updated', ($items->max('published_at') ?? now())->toAtomString());

        foreach ($items as $article) {
            $url = url($article->publicPath() ?? '/');
            $xml->startElement('entry');
            $xml->writeElement('title', $article->title);
            $xml->writeElement('id', $url);
            $this->link($xml, $url, 'alternate');
            $xml->writeElement('published', $article->published_at->toAtomString());
            $xml->writeElement('updated', ($article->lastUpdated() ?? $article->published_at)->toAtomString());
            $xml->startElement('author');
            $xml->writeElement('name', $article->author?->authorName() ?? $this->general->site_name);
            $xml->endElement();
            $this->categories($xml, $article, atom: true);
            $xml->writeElement('summary', (string) $article->excerpt);
            $xml->startElement('content');
            $xml->writeAttribute('type', 'html');
            $xml->text((string) $article->body_rendered);
            $xml->endElement();
            $xml->endElement();
        }
        $xml->endElement();

        return $this->respond($xml, 'application/atom+xml');
    }

    /** @param  Collection<int, Article>  $items */
    private function rss(string $title, string $link, ?string $description, string $self, Collection $items): Response
    {
        $xml = $this->document();
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->writeAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $xml->startElement('channel');
        $xml->writeElement('title', $title);
        $xml->writeElement('link', $link);
        $xml->writeElement('description', (string) $description);
        $xml->writeElement('language', 'en');
        $xml->writeElement('generator', $this->general->site_name);
        $xml->writeElement('lastBuildDate', ($items->max('published_at') ?? now())->toRssString());
        $xml->startElement('atom:link');
        $xml->writeAttribute('href', $self);
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/rss+xml');
        $xml->endElement();

        foreach ($items as $article) {
            $url = url($article->publicPath() ?? '/');
            $xml->startElement('item');
            $xml->writeElement('title', $article->title);
            $xml->writeElement('link', $url);
            $xml->startElement('guid');
            $xml->writeAttribute('isPermaLink', 'true');
            $xml->text($url);
            $xml->endElement();
            $xml->writeElement('pubDate', $article->published_at->toRssString());
            $xml->writeElement('dc:creator', $article->author?->authorName() ?? $this->general->site_name);
            $this->categories($xml, $article);
            $xml->writeElement('description', (string) $article->excerpt);
            $xml->startElement('content:encoded');
            $xml->writeCdata(str_replace(']]>', ']]]]><![CDATA[>', (string) $article->body_rendered));
            $xml->endElement();
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endElement();

        return $this->respond($xml, 'application/rss+xml');
    }

    /**
     * @param  Builder<Article>  $query
     * @return Collection<int, Article>
     */
    private function latest(Builder $query): Collection
    {
        return $query->with(['author', 'category', 'tags'])->latest('published_at')->limit(self::ITEMS)->get();
    }

    private function categories(XMLWriter $xml, Article $article, bool $atom = false): void
    {
        $terms = collect([$article->type === ArticleType::Blog ? 'Blog' : $article->category?->name])
            ->concat($article->tags->pluck('name'))->filter()->unique();
        foreach ($terms as $term) {
            if ($atom) {
                $xml->startElement('category');
                $xml->writeAttribute('term', $term);
                $xml->endElement();
            } else {
                $xml->writeElement('category', $term);
            }
        }
    }

    private function link(XMLWriter $xml, string $href, string $rel): void
    {
        $xml->startElement('link');
        $xml->writeAttribute('href', $href);
        $xml->writeAttribute('rel', $rel);
        $xml->endElement();
    }

    private function document(): XMLWriter
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        return $xml;
    }

    private function respond(XMLWriter $xml, string $type): Response
    {
        $xml->endDocument();

        return response($xml->outputMemory(), 200, [
            'Content-Type' => $type.'; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }
}
