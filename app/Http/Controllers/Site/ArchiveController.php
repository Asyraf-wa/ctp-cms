<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Series;
use App\Models\Tag;
use App\Support\Sidebar;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

// PRD PUB-07: category, tag and series archives. Renamed slugs reach here through the 404 redirect lookup.
class ArchiveController extends Controller
{
    public const PER_PAGE = 12;

    public function category(string $slug): View
    {
        $category = Category::where('slug', $slug)->with(['parent', 'children'])->firstOrFail();

        return view('site.category', [
            'category' => $category,
            'articles' => Article::published()->tutorials()->forCards()
                ->whereIn('category_id', $category->familyIds())
                ->orderByDesc('is_pinned')->latest('published_at')
                ->paginate(self::PER_PAGE),
            'sidebar' => Sidebar::categories(open: $category),
        ]);
    }

    public function tag(string $slug): View
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        return view('site.tag', [
            'tag' => $tag,
            'articles' => Article::published()->forCards()
                ->whereHas('tags', fn (Builder $q) => $q->whereKey($tag->id))
                ->latest('published_at')
                ->paginate(self::PER_PAGE),
        ]);
    }

    public function seriesIndex(): View
    {
        $series = Series::query()->orderBy('sort')->with('cover')
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->get()->filter(fn (Series $s) => $s->getAttribute('articles_count') > 0)->values();

        return view('site.series-index', ['series' => $series]);
    }

    public function series(string $slug): View
    {
        $series = Series::where('slug', $slug)->with('cover')->firstOrFail();
        $articles = $series->articles()->published()->forCards()->get();
        abort_if($articles->isEmpty(), 404);

        return view('site.series', ['series' => $series, 'articles' => $articles]);
    }
}
