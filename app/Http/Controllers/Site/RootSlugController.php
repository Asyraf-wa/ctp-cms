<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Support\Slugs\RootSlugs;
use Illuminate\Contracts\View\View;

// D9: domain/{slug} resolves to a tutorial or a page. Registered as the last route.
class RootSlugController extends Controller
{
    public function __invoke(string $slug): View
    {
        $model = RootSlugs::resolve($slug);

        return match (true) {
            $model instanceof Article => app(ArticleController::class)->show($model),
            $model instanceof Page => app(PageController::class)->show($model),
            default => abort(404),
        };
    }
}
