<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use Illuminate\Contracts\View\View;

// PRD AUTHOR-03: the author's profile and articles (E-E-A-T). Person JSON-LD is added in Phase 3.
class AuthorController extends Controller
{
    public function show(string $slug): View
    {
        $author = User::where('slug', $slug)->firstOrFail();
        $articles = Article::published()->forCards()->where('author_id', $author->id)->latest('published_at')->paginate(12);
        abort_if($articles->total() === 0 && ! $author->bio_short, 404);

        return view('site.author', [
            'author' => $author,
            'articles' => $articles,
            'stats' => [
                'tutorials' => Article::published()->tutorials()->where('author_id', $author->id)->count(),
                'posts' => Article::published()->blogPosts()->where('author_id', $author->id)->count(),
            ],
        ]);
    }
}
