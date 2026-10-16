<?php

namespace App\Http\Controllers\Site;

use App\Enums\Difficulty;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

// PRD PUB-02: every tutorial, filtered by category, tag and difficulty, sorted by latest or popular.
// Filters live in the query string so a filtered list can be shared and bookmarked.
class TutorialController extends Controller
{
    public const PER_PAGE = 12;

    public function index(Request $request): View
    {
        $category = $request->filled('category') ? Category::where('slug', $request->string('category'))->first() : null;
        $tag = $request->filled('tag') ? Tag::where('slug', $request->string('tag'))->first() : null;
        $difficulty = Difficulty::tryFrom((string) $request->query('difficulty'));
        $sort = $request->query('sort') === 'popular' ? 'popular' : 'latest';

        $articles = Article::published()->tutorials()->forCards()
            ->when($category, fn (Builder $q, Category $c) => $q->whereIn('category_id', $c->familyIds()))
            ->when($tag, fn (Builder $q, Tag $t) => $q->whereHas('tags', fn (Builder $q) => $q->whereKey($t->id)))
            ->when($difficulty, fn (Builder $q, Difficulty $d) => $q->where('difficulty', $d))
            ->when($sort === 'popular', fn (Builder $q) => $q->orderByDesc('views')->orderByDesc('kudos'))
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('site.tutorials', [
            'articles' => $articles,
            'category' => $category,
            'tag' => $tag,
            'difficulty' => $difficulty,
            'sort' => $sort,
            'categories' => Category::query()->roots()->ordered()->with('children')->get(),
            'counts' => Article::published()->tutorials()->selectRaw('category_id, count(*) as total')->groupBy('category_id')->pluck('total', 'category_id'),
            'popularTags' => Tag::query()->withCount(['articles' => fn ($q) => $q->published()->tutorials()])
                ->orderByDesc('articles_count')->limit(15)->get()->filter(fn (Tag $t) => $t->getAttribute('articles_count') > 0),
            'filtered' => $category || $tag || $difficulty,
        ]);
    }
}
