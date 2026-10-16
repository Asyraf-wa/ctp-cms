<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Project;
use App\Models\Series;
use App\Models\Tag;
use App\Support\Sidebar;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /**
     * Sample of a text-only home in the 3-column tutorial layout (/preview/home, not indexed):
     * categories left, 10 latest tutorials with category tabs in the centre, discovery panels right.
     */
    public function preview(Request $request): View
    {
        $categories = Category::query()->roots()->ordered()->with('children')->get()
            ->map(function (Category $category) {
                $category->setAttribute('tutorial_count', Article::published()->tutorials()->whereIn('category_id', $category->familyIds())->count());

                return $category;
            })
            ->filter(fn (Category $c) => $c->getAttribute('tutorial_count') > 0)
            ->values();

        $active = $categories->firstWhere('slug', (string) $request->query('category'));

        $latest = Article::published()->tutorials()->forCards()
            ->when($active, fn (Builder $q, Category $c) => $q->whereIn('category_id', $c->familyIds()))
            ->orderByDesc('is_pinned')->latest('published_at')
            ->limit(10)->get();

        $levels = Article::published()->tutorials()->whereNotNull('difficulty')
            ->selectRaw('difficulty, count(*) as total')->groupBy('difficulty')->pluck('total', 'difficulty');

        // Views from the last 30 days; all-time views until enough daily stats exist.
        $popular = Article::published()->tutorials()->select(['id', 'type', 'title', 'slug', 'views'])
            ->selectSub(DB::table('article_view_stats')->selectRaw('coalesce(sum(views), 0)')
                ->whereColumn('article_id', 'articles.id')->where('date', '>=', now()->subDays(30)->toDateString()), 'views_30d')
            ->orderByDesc('views_30d')->orderByDesc('views')->latest('published_at')
            ->limit(5)->get();

        return view('site.home-preview', [
            'sidebar' => Sidebar::categories(open: $active),
            'categories' => $categories,
            'active' => $active,
            'latest' => $latest,
            'tutorialCount' => Article::published()->tutorials()->count(),
            'levels' => $levels,
            'tags' => Tag::query()->withCount(['articles' => fn ($q) => $q->published()->tutorials()])
                ->orderByDesc('articles_count')->limit(15)->get()->filter(fn (Tag $t) => $t->getAttribute('articles_count') > 0),
            'popular' => $popular,
            'series' => Series::query()->orderBy('sort')
                ->withCount(['articles' => fn ($q) => $q->published()])
                ->get()->filter(fn (Series $s) => $s->getAttribute('articles_count') > 0)->take(4)->values(),
        ]);
    }
}
