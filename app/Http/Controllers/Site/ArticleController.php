<?php

namespace App\Http\Controllers\Site;

use App\Enums\ArticleType;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\Sidebar;
use Illuminate\Contracts\View\View;

// PRD PUB-03 (tutorial page) and PUB-08 (blog post). Phase 2 polishes the layout.
class ArticleController extends Controller
{
    public function show(Article $article, bool $preview = false): View
    {
        abort_unless($preview || $article->isPubliclyVisible(), 404);

        $article->loadMissing(['author', 'category.parent', 'tags', 'series', 'featuredMedia', 'seo']);

        $data = [
            'article' => $article,
            'preview' => $preview,
            'categories' => $article->type === ArticleType::Tutorial ? Sidebar::categories($article) : [],
            ...$this->neighbours($article),
        ];

        return $article->type === ArticleType::Blog ? view('site.blog-post', $data) : view('site.article', $data);
    }

    // PUB-08: single-column journal list.
    public function blogIndex(): View
    {
        return view('site.blog', [
            'posts' => Article::published()->blogPosts()->forCards()->latest('published_at')->paginate(10),
        ]);
    }

    public function blog(string $slug): View
    {
        $article = Article::blogPosts()->where('slug', $slug)->firstOrFail();

        return $this->show($article);
    }

    // Signed link from the admin (ART-04): drafts and scheduled articles in the real layout.
    public function preview(Article $article): View
    {
        return $this->show($article, preview: true);
    }

    /** @return array{previous: ?Article, next: ?Article} */
    private function neighbours(Article $article): array
    {
        if ($article->series_id && $article->series_position) {
            $siblings = Article::published()->where('series_id', $article->series_id);

            return [
                'previous' => (clone $siblings)->where('series_position', '<', $article->series_position)->orderByDesc('series_position')->first(),
                'next' => (clone $siblings)->where('series_position', '>', $article->series_position)->orderBy('series_position')->first(),
            ];
        }

        if (! $article->published_at) {
            return ['previous' => null, 'next' => null];
        }

        $siblings = Article::published()->where('type', $article->type)
            ->when($article->category_id, fn ($q) => $q->where('category_id', $article->category_id));

        return [
            'previous' => (clone $siblings)->where('published_at', '<', $article->published_at)->latest('published_at')->first(),
            'next' => (clone $siblings)->where('published_at', '>', $article->published_at)->oldest('published_at')->first(),
        ];
    }
}
