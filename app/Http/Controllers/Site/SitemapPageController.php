<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use App\Models\Series;
use App\Seo\Seo;
use Illuminate\Contracts\View\View;

// For people, not crawlers (those read /sitemap.xml): every section and tutorial on one page.
// Guest pages are response-cached, and saving content clears that cache, so this needs no cache of its own.
class SitemapPageController extends Controller
{
    public function __invoke(): View
    {
        $tutorials = Article::published()->tutorials()->orderBy('title')->get(['id', 'type', 'title', 'slug', 'category_id'])->toBase()->groupBy('category_id');

        $groups = Category::query()->roots()->ordered()->with('children')->get()
            ->map(function (Category $category) use ($tutorials) {
                $ids = [$category->id, ...$category->children->pluck('id')];

                return ['category' => $category, 'articles' => $tutorials->only($ids)->flatten()->sortBy('title')->values()];
            })
            ->filter(fn (array $group) => $group['articles']->isNotEmpty())
            ->values();

        return view('site.sitemap', [
            'groups' => $groups,
            'posts' => Article::published()->blogPosts()->latest('published_at')->get(['id', 'type', 'title', 'slug', 'published_at']),
            'series' => Series::query()->orderBy('sort')->whereHas('articles', fn ($q) => $q->published())->get(),
            'projects' => Project::published()->get(),
            'pages' => Page::query()->where('is_published', true)->orderBy('sort')->get(['id', 'title', 'slug']),
            'meta' => app(Seo::class)->listing('Sitemap', 'Every tutorial, blog post, series, project and page on one screen.', [['Sitemap', null]]),
        ]);
    }
}
