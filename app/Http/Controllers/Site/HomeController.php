<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Project;
use App\Models\Series;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

// PRD PUB-01, DESIGN §6: hero, featured, latest, categories, learning paths, blog, projects.
class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Article::published()->tutorials()->forCards()
            ->where(fn (Builder $q) => $q->where('is_featured', true)->orWhere('is_pinned', true))
            ->orderByDesc('is_pinned')->latest('published_at')->limit(3)->get();

        $latest = Article::published()->tutorials()->forCards()
            ->whereNotIn('id', $featured->modelKeys())
            ->latest('published_at')->limit(6)->get();

        $categories = Category::query()->roots()->ordered()->with('children')->get()
            ->map(function (Category $category) {
                $category->setAttribute('tutorial_count', Article::published()->tutorials()->whereIn('category_id', $category->familyIds())->count());

                return $category;
            })
            ->filter(fn (Category $c) => $c->getAttribute('tutorial_count') > 0)
            ->values();

        $series = Series::query()->orderBy('sort')->with('cover')
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->get()->filter(fn (Series $s) => $s->getAttribute('articles_count') > 0)->take(3)->values();

        return view('site.home', [
            'featured' => $featured,
            'latest' => $latest,
            'categories' => $categories,
            'series' => $series,
            'posts' => Article::published()->blogPosts()->forCards()->latest('published_at')->limit(3)->get(),
            'projects' => Project::published()->with('cover')->orderByDesc('is_featured')->orderBy('sort')->limit(3)->get(),
            'tutorialCount' => Article::published()->tutorials()->count(),
        ]);
    }
}
