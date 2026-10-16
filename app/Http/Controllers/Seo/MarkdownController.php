<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Seo\Llms;
use App\Support\Slugs\RootSlugs;
use Illuminate\Http\Response;

// GEO-01, GEO-02: Markdown twins of public content, plus llms.txt / llms-full.txt.
class MarkdownController extends Controller
{
    public function __construct(private Llms $llms) {}

    public function root(string $slug): Response
    {
        $model = RootSlugs::resolve($slug);
        abort_unless(($model instanceof Article || $model instanceof Page) && $model->isPubliclyVisible(), 404);

        return $this->markdown($model instanceof Article ? $this->llms->article($model) : $this->llms->page($model), url($model->publicPath() ?? '/'));
    }

    public function blog(string $slug): Response
    {
        $article = Article::published()->blogPosts()->where('slug', $slug)->firstOrFail();

        return $this->markdown($this->llms->article($article), url($article->publicPath() ?? '/'));
    }

    public function index(): Response
    {
        return $this->markdown($this->llms->index(), null, 'text/plain');
    }

    public function full(): Response
    {
        return $this->markdown($this->llms->full(), null, 'text/plain');
    }

    private function markdown(string $body, ?string $canonical, string $type = 'text/markdown'): Response
    {
        $headers = ['Content-Type' => $type.'; charset=UTF-8', 'Cache-Control' => 'public, max-age=900'];
        if ($canonical) {
            // Search engines should index the HTML page, not this copy.
            $headers['Link'] = '<'.$canonical.'>; rel="canonical"';
            $headers['X-Robots-Tag'] = 'noindex';
        }

        return response($body, 200, $headers);
    }
}
