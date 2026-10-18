<?php

use App\Jobs\SubmitToIndexNow;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Tag;
use App\Settings\IntegrationSettings;
use App\Settings\SeoSettings;
use Database\Factories\ArticleFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->cake = Category::factory()->create(['name' => 'CakePHP']);
});

it('lists only indexable content in the sitemaps, with images', function () {
    $image = Media::factory()->create();
    $article = Article::factory()->published()->create(['title' => 'Bake Basics', 'category_id' => $this->cake->id, 'featured_media_id' => $image->id,
        'body' => ArticleFactory::doc([['paragraph', 'Hi']])]);
    Article::factory()->create(['title' => 'Draft', 'category_id' => $this->cake->id]);
    $noindex = Article::factory()->published()->create(['title' => 'Hidden', 'category_id' => $this->cake->id]);
    $noindex->seo()->create(['robots' => 'noindex, follow']);
    Page::factory()->create(['title' => 'About']);
    $lonely = Tag::factory()->create(['name' => 'Lonely']);
    $article->tags()->attach($lonely);

    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('sitemap.section', 'articles'), false)
        ->assertSee(route('sitemap.section', 'pages'), false)
        ->assertDontSee(route('sitemap.section', 'tags'), false); // no tag with two articles yet

    $this->get('/sitemaps/articles.xml')->assertOk()
        ->assertSee('<loc>'.url('/'.$article->slug).'</loc>', false)
        ->assertSee('<image:loc>'.$image->url().'</image:loc>', false)
        ->assertDontSee('/draft', false)
        ->assertDontSee(url('/'.$noindex->slug), false);
    $this->get('/sitemaps/pages.xml')->assertSee('<loc>'.url('/about').'</loc>', false)->assertSee('<loc>'.url('/tutorials').'</loc>', false);
    $this->get('/sitemaps/tags.xml')->assertNotFound();
    $this->get('/sitemaps/nonsense.xml')->assertNotFound();

    // A new article clears the cache.
    Article::factory()->published()->create(['title' => 'Fresh One', 'category_id' => $this->cake->id]);
    $this->get('/sitemaps/articles.xml')->assertSee('/fresh-one', false);
});

it('serves robots.txt: blocked outside production, and the AI crawler policy in production', function () {
    $this->get('/robots.txt')->assertOk()->assertSee("User-agent: *\nDisallow: /", false)->assertSee('Sitemap: '.route('sitemap'), false);

    config(['ctp.seo.allow_indexing' => true]);
    updateSettings(IntegrationSettings::class, ['ai_crawlers' => ['GPTBot' => false] + array_fill_keys(array_keys(IntegrationSettings::AI_CRAWLERS), true)]);
    updateSettings(SeoSettings::class, ['robots_extra' => "User-agent: AhrefsBot\nDisallow: /"]);

    $this->get('/robots.txt')->assertOk()
        ->assertSee("Disallow: /admin\nDisallow: /preview/", false)
        ->assertSee("User-agent: GPTBot\nDisallow: /", false)
        ->assertSee("User-agent: ClaudeBot\nAllow: /", false)
        ->assertSee("User-agent: AhrefsBot\nDisallow: /", false);
});

it('publishes RSS and Atom feeds with full content', function () {
    $tutorial = Article::factory()->published()->create(['title' => 'Bake & Serve', 'category_id' => $this->cake->id, 'body' => ArticleFactory::doc([['paragraph', 'Full body text.']])]);
    $post = Article::factory()->published()->blog()->create(['title' => 'Journal']);
    Article::factory()->create(['title' => 'Secret Draft', 'category_id' => $this->cake->id]);

    $rss = $this->get('/feed')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')->getContent();
    $doc = simplexml_load_string($rss);
    expect((string) $doc->channel->title)->toBe('Code The Pixel')
        ->and(array_map('strval', $doc->xpath('//item/title')))->toContain('Bake & Serve', 'Journal')->not->toContain('Secret Draft')
        ->and($rss)->toContain('<![CDATA[<p>Full body text.</p>');

    $this->get('/feed/blog')->assertSee('Journal')->assertDontSee('Bake &amp; Serve', false);
    $this->get('/feed/category/cakephp')->assertSee('Bake &amp; Serve', false)->assertDontSee('Journal');
    $this->get('/feed/category/missing')->assertNotFound();

    $atom = simplexml_load_string($this->get('/feed/atom')->assertOk()->getContent());
    expect((string) $atom->entry[0]->id)->toStartWith(url('/'));

    // Advertised on every page.
    $this->get('/')->assertSee('<link rel="alternate" type="application/rss+xml" href="'.url('/feed').'"', false);
});

it('pings IndexNow once for published changes, only in production with a key', function () {
    Queue::fake();
    $article = Article::factory()->published()->create(['title' => 'Bake Basics', 'category_id' => $this->cake->id]);
    Queue::assertNothingPushed(); // disabled outside production

    config(['ctp.seo.indexnow' => true]);
    updateSettings(IntegrationSettings::class, ['indexnow_key' => 'abc123def456']);
    Cache::flush();

    $article->update(['title' => 'Bake Basics Updated']);
    $article->update(['excerpt' => 'Again within ten minutes']);
    Queue::assertPushed(SubmitToIndexNow::class, 1);
    Queue::assertPushed(SubmitToIndexNow::class, fn ($job) => $job->urls === [url('/'.$article->slug)]);

    // Drafts are not announced; renamed slugs announce old and new URLs.
    Article::factory()->create(['category_id' => $this->cake->id]);
    Queue::assertPushed(SubmitToIndexNow::class, 1);
    Cache::flush();
    $article->update(['slug' => 'bake-basics-2026']);
    Queue::assertPushed(SubmitToIndexNow::class, fn ($job) => $job->urls === [url('/bake-basics-2026'), url('/'.'bake-basics')]);
});

it('sends the IndexNow request and serves the key file', function () {
    Http::fake(['api.indexnow.org/*' => Http::response(null, 202)]);
    updateSettings(IntegrationSettings::class, ['indexnow_key' => 'abc123def456']);

    (new SubmitToIndexNow([url('/bake-basics')]))->handle(app(IntegrationSettings::class));

    Http::assertSent(fn ($request) => $request['key'] === 'abc123def456'
        && $request['keyLocation'] === url('/abc123def456.txt')
        && $request['urlList'] === [url('/bake-basics')]);

    $this->get('/abc123def456.txt')->assertOk()->assertSeeText('abc123def456');
    $this->get('/wrongkey99.txt')->assertNotFound();
});
