<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Search\Search;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// PRD PUB-06: the /search results page and the JSON endpoint behind the Ctrl/⌘ K palette.
class SearchController extends Controller
{
    public function __construct(private Search $search) {}

    public function page(Request $request): View
    {
        $query = Search::normalise($request->query('q'));
        $quick = $query !== '' ? $this->search->quick($query) : null;

        return view('site.search', [
            'query' => $query,
            'results' => $query !== '' ? $this->search->paginate($query) : null,
            'categories' => $quick['categories'] ?? collect(),
            'tags' => $quick['tags'] ?? collect(),
            'popular' => Article::published()->tutorials()->forCards()->orderByDesc('views')->limit(6)->get(),
        ]);
    }

    public function api(Request $request): JsonResponse
    {
        $query = Search::normalise($request->query('q'));
        if (mb_strlen($query) < 2) {
            return response()->json(['query' => $query, 'groups' => []]);
        }

        $results = $this->search->quick($query);
        $article = fn (Article $a) => [
            'title' => $a->title,
            'url' => url($a->publicPath() ?? '/'),
            'excerpt' => str((string) $a->excerpt)->limit(110)->value(),
            'meta' => $a->type->value === 'blog' ? 'Blog' : $a->category?->name,
        ];

        $groups = array_values(array_filter([
            ['label' => 'Tutorials', 'items' => $results['tutorials']->map($article)->all()],
            ['label' => 'Blog', 'items' => $results['blog']->map($article)->all()],
            ['label' => 'Categories', 'items' => $results['categories']->map(fn (Category $c) => ['title' => $c->name, 'url' => url($c->publicPath() ?? '/'), 'meta' => 'Category'])->all()],
            ['label' => 'Tags', 'items' => $results['tags']->map(fn (Tag $t) => ['title' => '#'.$t->name, 'url' => url($t->publicPath() ?? '/'), 'meta' => 'Tag'])->all()],
        ], fn (array $g) => $g['items'] !== []));

        return response()->json(['query' => $query, 'groups' => $groups])
            ->header('Cache-Control', 'public, max-age=60');
    }
}
