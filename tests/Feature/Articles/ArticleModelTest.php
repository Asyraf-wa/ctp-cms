<?php

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Models\Article;
use App\Models\Media;
use App\Models\Page;
use App\Models\RootSlug;
use Database\Factories\ArticleFactory;

it('renders the body, TOC, text and reading time on save', function () {
    $article = Article::factory()->create(['body' => ArticleFactory::doc([
        ['heading', 'Install CakePHP', 1],
        ['paragraph', str_repeat('word ', 440)],
        ['heading', 'Configure', 2],
        ['codeBlock', "<?php\necho 'hi';", 'php'],
    ])]);

    expect($article->body_rendered)
        ->toContain('<h2 id="install-cakephp">Install CakePHP</h2>') // H1 promoted
        ->toContain('class="code-block"')
        ->toContain('hl-keyword')
        ->and(collect($article->toc)->pluck('id')->all())->toBe(['install-cakephp', 'configure'])
        ->and($article->word_count)->toBeGreaterThanOrEqual(440)
        ->and($article->reading_minutes)->toBe(3) // 440/220 = 2, plus a code block
        ->and($article->body_text)->toContain('Install CakePHP');
});

it('renders custom blocks', function () {
    $article = Article::factory()->create(['body' => ArticleFactory::doc([
        ['block', 'callout', ['type' => 'warning', 'body' => 'Back up **first**.']],
        ['block', 'code', ['language' => 'bash', 'filename' => 'deploy.sh', 'code' => 'php artisan migrate', 'highlight' => '1']],
        ['block', 'steps', ['steps' => [['title' => 'Install'], ['title' => 'Run']]]],
        ['block', 'youtube', ['url' => 'https://youtu.be/dQw4w9WgXcQ', 'title' => 'Demo']],
    ])]);

    expect($article->body_rendered)
        ->toContain('callout--warning')->toContain('<strong>first</strong>')
        ->toContain('deploy.sh')->toContain('code-block__mark')
        ->toContain('class="steps"')
        ->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ');
});

it('publishes now, schedules future dates and keeps drafts as drafts', function () {
    $now = Article::factory()->create(['status' => ArticleStatus::Published]);
    $later = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->addDay()]);
    $draft = Article::factory()->create();

    expect($now->status)->toBe(ArticleStatus::Published)->and($now->published_at)->not->toBeNull()
        ->and($later->status)->toBe(ArticleStatus::Scheduled)->and($later->isPubliclyVisible())->toBeFalse()
        ->and($draft->status)->toBe(ArticleStatus::Draft)->and($draft->published_at)->toBeNull();

    expect(Article::published()->pluck('id')->all())->toBe([$now->id]);
});

it('puts tutorials in the root namespace and blog posts under /blog', function () {
    $tutorial = Article::factory()->create(['title' => 'CakePHP CRUD']);
    $post = Article::factory()->blog()->create(['title' => 'CakePHP CRUD']);

    expect($tutorial->publicPath())->toBe('/cakephp-crud')
        ->and($post->publicPath())->toBe('/blog/cakephp-crud') // same slug allowed across types
        ->and(RootSlug::find('cakephp-crud')->sluggable->is($tutorial))->toBeTrue();
});

it('keeps tutorial slugs clear of pages and reserved words', function () {
    Page::factory()->create(['title' => 'About']);

    expect(Article::factory()->create(['title' => 'About'])->slug)->toBe('about-2')
        ->and(Article::factory()->create(['title' => 'Blog'])->slug)->toBe('blog-2')
        ->and(Article::factory()->blog()->create(['title' => 'About'])->slug)->toBe('about');
});

it('redirects the old URL when a published tutorial becomes a blog post', function () {
    $article = Article::factory()->published()->create(['title' => 'My Journey']);
    $article->update(['type' => ArticleType::Blog]);

    expect(RootSlug::find('my-journey'))->toBeNull();
    $this->get('/my-journey')->assertRedirect('/blog/my-journey');
});

it('frees the root slug when trashed and takes it back when restored', function () {
    $article = Article::factory()->create(['title' => 'Temp']);

    $article->delete();
    expect(RootSlug::find('temp'))->toBeNull();

    $article->restore();
    expect(RootSlug::find('temp'))->not->toBeNull();
});

it('records media used in the body and as featured image', function () {
    [$hero, $inline, $block] = Media::factory()->count(3)->create();

    $article = Article::factory()->create([
        'featured_media_id' => $hero->id,
        'body' => ArticleFactory::doc([['image', $inline->id], ['block', 'image', ['media_id' => $block->id, 'alt' => 'x']]]),
    ]);

    expect($hero->isInUse() && $inline->isInUse() && $block->isInUse())->toBeTrue()
        ->and($article->body_rendered)->toContain('article-figure');
});

it('flags outdated tutorials', function () {
    config(['ctp.freshness_months' => 24]);

    expect(Article::factory()->published(now()->subYears(3))->create()->isOutdated())->toBeTrue()
        ->and(Article::factory()->published(now()->subYears(3))->create(['content_updated_at' => now()->subMonth()])->isOutdated())->toBeFalse()
        ->and(Article::factory()->published()->create(['needs_review' => true])->isOutdated())->toBeTrue();
});
