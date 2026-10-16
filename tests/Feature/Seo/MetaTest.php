<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Project;
use App\Models\Technology;
use App\Models\User;
use App\Seo\OgImage;
use Database\Factories\ArticleFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;

/** @return array<string, list<array<string, mixed>>> JSON-LD nodes grouped by @type */
function jsonLd(TestResponse $response): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $m);
    $doc = json_decode($m[1] ?? 'null', true, flags: JSON_THROW_ON_ERROR);

    return collect($doc['@graph'])->groupBy('@type')->map->all()->all();
}

beforeEach(function () {
    $this->cake = Category::factory()->create(['name' => 'CakePHP']);
    $this->author = User::factory()->create(['name' => 'Asyraf Wahi Anuar', 'job_title' => 'Lecturer']);
});

it('describes a tutorial with meta tags, a generated card and TechArticle, FAQ, HowTo and breadcrumbs', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Bake Basics', 'excerpt' => 'Generate CRUD in minutes.', 'category_id' => $this->cake->id, 'author_id' => $this->author->id,
        'faq' => [['question' => 'Does Bake work on CakePHP 5?', 'answer' => 'Yes.']],
        'body' => ArticleFactory::doc([['heading', 'Install'], ['block', 'steps', ['steps' => [['title' => 'Install', 'body' => 'Run composer.'], ['title' => 'Bake', 'body' => 'Run bin/cake bake.']]]]]),
    ]);
    $article->techStack()->create(['technology_id' => Technology::factory()->create(['name' => 'CakePHP'])->id, 'version' => '5.2', 'sort' => 0]);

    $response = $this->get('/'.$article->slug)->assertOk()
        ->assertSee('<title>Bake Basics · Code The Pixel</title>', false)
        ->assertSee('<link rel="canonical" href="'.url('/'.$article->slug).'">', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('<meta property="og:image" content="'.OgImage::url('article', $article->fresh()).'">', false)
        ->assertSee('<link rel="alternate" type="text/markdown" href="'.url('/'.$article->slug.'.md').'"', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);

    $ld = jsonLd($response);
    expect(array_keys($ld))->toContain('WebSite', 'Organization', 'BreadcrumbList', 'TechArticle', 'Person', 'FAQPage', 'HowTo')
        ->and($ld['TechArticle'][0])->toMatchArray(['headline' => 'Bake Basics', 'articleSection' => 'CakePHP', 'dependencies' => 'CakePHP 5.2'])
        ->and($ld['TechArticle'][0]['author']['@id'])->toBe($ld['Person'][0]['@id'])
        ->and(collect($ld['BreadcrumbList'][0]['itemListElement'])->pluck('name')->all())->toBe(['Home', 'Tutorials', 'CakePHP', 'Bake Basics'])
        ->and($ld['HowTo'][0]['step'])->toHaveCount(2)
        ->and($ld['FAQPage'][0]['mainEntity'][0]['name'])->toBe('Does Bake work on CakePHP 5?')
        ->and($ld['WebSite'][0]['potentialAction']['target']['urlTemplate'])->toBe(url('/search').'?q={search_term_string}');
});

it('uses BlogPosting for blog posts and honours per-article SEO overrides', function () {
    $og = Media::factory()->create();
    $post = Article::factory()->published()->blog()->create(['title' => 'Why Laravel', 'author_id' => $this->author->id]);
    $post->seo()->create(['title' => 'Why I Moved to Laravel', 'description' => 'A short story.', 'canonical' => 'https://dev.to/x', 'robots' => 'noindex, follow', 'og_media_id' => $og->id]);

    $response = $this->get('/blog/'.$post->slug)->assertOk()
        ->assertSee('<title>Why I Moved to Laravel · Code The Pixel</title>', false)
        ->assertSee('<meta name="description" content="A short story.">', false)
        ->assertSee('<link rel="canonical" href="https://dev.to/x">', false)
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<meta property="og:image" content="'.$og->url().'">', false);

    expect(jsonLd($response))->toHaveKey('BlogPosting')->not->toHaveKey('TechArticle');
});

it('escapes titles safely inside the JSON-LD script', function () {
    $article = Article::factory()->published()->create(['title' => 'Close </script><script>alert(1)</script>', 'category_id' => $this->cake->id]);

    $html = $this->get('/'.$article->slug)->assertOk()->getContent();
    expect($html)->not->toContain('</script><script>alert(1)')
        ->and(jsonLd($this->get('/'.$article->slug))['TechArticle'][0]['headline'])->toBe('Close </script><script>alert(1)</script>');
});

it('marks previews noindex without a markdown link', function () {
    $draft = Article::factory()->create(['title' => 'Draft', 'category_id' => $this->cake->id]);

    $this->get(URL::temporarySignedRoute('articles.preview', now()->addHour(), ['article' => $draft]))
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertDontSee('type="text/markdown"', false);
});

it('describes projects, archives, authors and the home page', function () {
    $project = Project::factory()->create(['title' => 'Pixel Maps', 'repo_url' => 'https://github.com/x/maps']);
    Article::factory()->published()->create(['title' => 'In The Category', 'category_id' => $this->cake->id, 'author_id' => $this->author->id]);

    expect(jsonLd($this->get('/projects/'.$project->slug))['SoftwareSourceCode'][0])->toMatchArray(['name' => 'Pixel Maps', 'codeRepository' => 'https://github.com/x/maps'])
        ->and(jsonLd($this->get('/category/cakephp'))['CollectionPage'][0]['mainEntity']['itemListElement'][0]['name'])->toBe('In The Category')
        ->and(jsonLd($this->get('/author/'.$this->author->slug)))->toHaveKeys(['ProfilePage', 'Person'])
        ->and(array_keys(jsonLd($this->get('/'))))->toBe(['WebSite', 'Organization']);

    $this->get('/')->assertSee('<link rel="canonical" href="'.url('/').'">', false);
});

it('gives each page of a list its own canonical and prev/next links', function () {
    Article::factory()->count(14)->published()->create(['category_id' => $this->cake->id]);

    $this->get('/category/cakephp?page=2')->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/category/cakephp').'?page=2">', false)
        ->assertSee('<link rel="prev" href="'.url('/category/cakephp').'?page=1">', false)
        ->assertDontSee('rel="next"', false);
});

it('renders and caches a 1200×630 PNG card, and refuses stale keys and drafts', function () {
    Storage::fake('public');
    $article = Article::factory()->published()->create(['title' => 'A Title Long Enough To Wrap Onto A Second Line Of The Card', 'category_id' => $this->cake->id]);
    $url = OgImage::url('article', $article);
    $key = OgImage::key('article', $article);

    $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get("og/{$key}.png"));
    expect($response->headers->get('Cache-Control'))->toContain('immutable')
        ->and([$width, $height])->toBe([1200, 630]);

    $this->get(route('og', ['key' => 'article-'.$article->id.'-00000000']))->assertNotFound();
    $draft = Article::factory()->create(['category_id' => $this->cake->id]);
    $this->get(OgImage::url('article', $draft))->assertNotFound();

    // A new title is a new key, so caches never serve the old card.
    $article->update(['title' => 'Renamed']);
    expect(OgImage::key('article', $article->fresh()))->not->toBe($key);
});
